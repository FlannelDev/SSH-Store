<?php

namespace App\Plugins\ShadowStore\Models;

use App\Enums\SuspendAction;
use App\Models\Allocation;
use App\Models\Server;
use App\Models\User;
use App\Models\Node;
use App\Services\Servers\SuspensionService;
use App\Services\Servers\ServerCreationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Exception;
use Throwable;

class Order extends Model
{
    protected $table = 'store_orders';

    // CPU types that are suitable for different game categories
    const GAMING_CPU_TYPES = ['gaming', 'workstation'];
    const ALL_CPU_TYPES = ['gaming', 'workstation', 'server', 'budget'];
    
    // Games that require high single-thread performance
    const HIGH_PERF_GAMES = ['arma-reforger', 'arma-3', 'dayz', 'squad'];

    protected $fillable = [
        'order_number',
        'user_id',
        'product_id',
        'tier_id',
        'tier_label',
        'coupon_code',
        'status',
        'slots',
        'variables',
        'billing_period',
        'currency',
        'subtotal',
        'tax',
        'total',
        'payment_method',
        'payment_id',
        'subscription_id',
        'server_id',
        'expires_at',
        'bill_due_at',
        'due_notice_sent_at',
        'past_due_notice_sent_at',
        'suspended_for_nonpayment_at',
        'suspended_notice_sent_at',
        'auto_renew',
        'notes',
    ];

    protected $casts = [
        'variables' => 'array',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'auto_renew' => 'boolean',
        'expires_at' => 'datetime',
        'bill_due_at' => 'datetime',
        'due_notice_sent_at' => 'datetime',
        'past_due_notice_sent_at' => 'datetime',
        'suspended_for_nonpayment_at' => 'datetime',
        'suspended_notice_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $order): void {
            if ($order->isDirty('bill_due_at')) {
                $order->due_notice_sent_at = null;
                $order->past_due_notice_sent_at = null;
                $order->suspended_for_nonpayment_at = null;
                $order->suspended_notice_sent_at = null;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(ProductTier::class, 'tier_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get calculated order specifications
     */
    public function getOrderSpecs(): array
    {
        $this->loadMissing(['product', 'tier']);
        
        $product = $this->product;
        if (!$product) {
            return [];
        }

        $slots = $this->slots ?? $product->default_slots ?? 64;
        $memory = $this->calculateMemory($slots);
        $cpu = $this->calculateCpu($slots);
        
        return [
            'product_name' => $product->name,
            'plan_name' => $this->tier_label ?: ($this->tier?->name ?? null),
            'game' => ucwords(str_replace('-', ' ', $product->game ?? 'Unknown')),
            'category' => ucfirst($product->category ?? ''),
            'slots' => $slots,
            'memory_mb' => $memory,
            'memory_gb' => round($memory / 1024, 1),
            'cpu_units' => $cpu,
            'disk_gb' => 1000,
            'billing_type' => ucfirst($product->billing_type ?? ''),
            'features' => $product->features ?? [],
        ];
    }

    public static function generateOrderNumber(): string
    {
        return 'SH-' . strtoupper(uniqid());
    }

    public function activate(?string $paymentId = null, ?string $subscriptionId = null): void
    {
        $this->update([
            'status' => 'paid',
            'payment_id' => $paymentId,
            'subscription_id' => $subscriptionId,
            'expires_at' => now()->addMonth(),
            'bill_due_at' => now()->addMonth(),
        ]);

        $this->releaseNonPaymentSuspension();

        if (!$this->server_id) {
            try {
                $this->createServer();
            } catch (Exception $e) {
                report($e);
                $this->update(['notes' => 'Server creation failed: ' . $e->getMessage()]);
            }
        }
    }

    public function releaseNonPaymentSuspension(): void
    {
        if (!$this->server_id) {
            return;
        }

        $this->loadMissing('server');

        if (!$this->server) {
            return;
        }

        try {
            if ($this->server->isSuspended()) {
                app(SuspensionService::class)->handle($this->server, SuspendAction::Unsuspend);
            }

            $this->forceFill([
                'suspended_for_nonpayment_at' => null,
                'suspended_notice_sent_at' => null,
            ])->save();

            if (SchemaFacade::hasTable('store_server_billings')) {
                ServerBilling::query()
                    ->where('server_id', $this->server_id)
                    ->update([
                        'suspended_for_nonpayment_at' => null,
                        'suspended_notice_sent_at' => null,
                    ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    protected function findConsecutiveAllocations(int $nodeId, int $count = 3): ?array
    {
        $allocations = Allocation::where('node_id', $nodeId)
            ->whereNull('server_id')
            ->orderBy('ip')
            ->orderBy('port')
            ->get()
            ->groupBy('ip');

        foreach ($allocations as $ip => $ipAllocations) {
            $ports = $ipAllocations->pluck('port')->sort()->values()->toArray();
            
            for ($i = 0; $i <= count($ports) - $count; $i++) {
                $consecutive = true;
                for ($j = 0; $j < $count - 1; $j++) {
                    if ($ports[$i + $j + 1] - $ports[$i + $j] !== 1) {
                        $consecutive = false;
                        break;
                    }
                }
                
                if ($consecutive) {
                    $selectedPorts = array_slice($ports, $i, $count);
                    return $ipAllocations->whereIn('port', $selectedPorts)
                        ->sortBy('port')
                        ->values()
                        ->toArray();
                }
            }
        }

        $anyAllocations = Allocation::where('node_id', $nodeId)
            ->whereNull('server_id')
            ->orderBy('port')
            ->limit($count)
            ->get()
            ->toArray();

        return count($anyAllocations) >= $count ? $anyAllocations : null;
    }

    /**
     * Check if this product requires high-performance gaming CPUs
     */
    protected function requiresGamingCpu(): bool
    {
        $product = $this->product;
        $game = strtolower($product->game ?? '');
        
        return in_array($game, self::HIGH_PERF_GAMES);
    }

    /**
     * Get allowed CPU types for this product
     */
    protected function getAllowedCpuTypes(): array
    {
        if ($this->requiresGamingCpu()) {
            return self::GAMING_CPU_TYPES;
        }
        return self::ALL_CPU_TYPES;
    }

    protected function getAvailableNodeIds(): array
    {
        $product = $this->product;
        
        if (!empty($product->node_ids)) {
            $nodeIds = $product->node_ids;
        } else {
            $nodeIds = Node::pluck('id')->toArray();
        }
        
        if (!empty($product->excluded_node_ids)) {
            $nodeIds = array_diff($nodeIds, $product->excluded_node_ids);
        }
        
        return array_values($nodeIds);
    }

    /**
     * Get nodes sorted by available resources and CPU suitability
     */
    protected function getNodesSortedByCapacity(int $requiredMemoryMB): array
    {
        $availableNodeIds = $this->getAvailableNodeIds();
        
        if (empty($availableNodeIds)) {
            return [];
        }

        $allowedCpuTypes = $this->getAllowedCpuTypes();
        $nodes = Node::whereIn('id', $availableNodeIds)->get();
        $nodeScores = [];

        foreach ($nodes as $node) {
            try {
                // Check CPU type compatibility
                $cpuType = $node->cpu_type ?? 'unknown';
                if (!in_array($cpuType, $allowedCpuTypes) && $cpuType !== 'unknown') {
                    continue; // Skip nodes with incompatible CPU types
                }

                $stats = $node->statistics();
                
                // Convert bytes to MB
                $memoryFreeMB = ($stats['memory_total'] - $stats['memory_used']) / 1024 / 1024;
                $diskFreeMB = ($stats['disk_total'] - $stats['disk_used']) / 1024 / 1024;
                
                // Skip nodes that don't have enough free memory (20% buffer)
                $requiredWithBuffer = $requiredMemoryMB * 1.2;
                if ($memoryFreeMB < $requiredWithBuffer) {
                    continue;
                }

                // Check if node has free allocations
                $freeAllocations = Allocation::where('node_id', $node->id)
                    ->whereNull('server_id')
                    ->count();
                
                if ($freeAllocations < 3) {
                    continue;
                }

                $nodeScores[] = [
                    'node_id' => $node->id,
                    'node_name' => $node->name,
                    'cpu_type' => $cpuType,
                    'cpu_score' => $node->cpu_score ?? 0,
                    'cpu_model' => $node->cpu_model ?? 'Unknown',
                    'memory_free_mb' => $memoryFreeMB,
                    'disk_free_mb' => $diskFreeMB,
                    'free_allocations' => $freeAllocations,
                    'cpu_load' => $stats['cpu_percent'] ?? 0,
                ];
            } catch (Exception $e) {
                continue;
            }
        }

        // Sort by: 1) CPU score (highest first), 2) Free memory, 3) CPU load (lowest)
        usort($nodeScores, function ($a, $b) {
            // Primary: highest CPU score for gaming workloads
            if ($a['cpu_score'] !== $b['cpu_score']) {
                return $b['cpu_score'] <=> $a['cpu_score'];
            }
            // Secondary: most free memory
            $memDiff = $b['memory_free_mb'] - $a['memory_free_mb'];
            if (abs($memDiff) > 1024) {
                return $memDiff > 0 ? 1 : -1;
            }
            // Tertiary: lowest CPU load
            return $a['cpu_load'] <=> $b['cpu_load'];
        });

        return array_column($nodeScores, 'node_id');
    }

    /**
     * Calculate memory based on player slots (max 32GB)
     */
    protected function calculateMemory(int $slots): int
    {
        $product = $this->product;
        
        if ($product->memory_per_slot) {
            $base = 8192;
            return min(32768, $base + ($slots * $product->memory_per_slot));
        }
        
        if ($slots <= 16) {
            return 8192;
        } elseif ($slots <= 32) {
            return 12288;
        } elseif ($slots <= 48) {
            return 16384;
        } elseif ($slots <= 64) {
            return 20480;
        } elseif ($slots <= 96) {
            return 24576;
        } else {
            return 32768;
        }
    }

    protected function calculateCpu(int $slots): int
    {
        $product = $this->product;
        
        if ($product->cpu_per_slot) {
            return $slots * $product->cpu_per_slot;
        }
        
        return min(600, 200 + ($slots * 3));
    }

    public function createServer(): ?Server
    {
        if ($this->server_id) {
            return $this->server;
        }

        $product = $this->product;
        if (!$product || !$product->egg_id) {
            throw new Exception('Product has no egg configured');
        }

        $product->load('egg.variables');

        $environment = [];
        foreach ($product->egg->variables as $variable) {
            if (isset($this->variables[$variable->env_variable])) {
                $environment[$variable->env_variable] = $this->variables[$variable->env_variable];
            } else {
                $environment[$variable->env_variable] = $variable->default_value;
            }
        }

        $slots = $this->slots ?? $product->default_slots ?? 64;
        $memory = $this->calculateMemory($slots);
        $cpu = $this->calculateCpu($slots);
        // Enforce a fixed 1TB allocation for all provisioned servers.
        $disk = 1000000;

        // Get nodes sorted by capacity and CPU suitability
        $sortedNodeIds = $this->getNodesSortedByCapacity($memory);
        
        if (empty($sortedNodeIds)) {
            $cpuTypes = implode(', ', $this->getAllowedCpuTypes());
            throw new Exception("No nodes available with sufficient resources (need " . round($memory/1024, 1) . "GB RAM) and compatible CPU type ({$cpuTypes})");
        }

        // Find a node with consecutive allocations
        $allocations = null;
        $selectedNodeId = null;
        $portsNeeded = $product->allocations ?? 3;

        foreach ($sortedNodeIds as $nodeId) {
            $allocations = $this->findConsecutiveAllocations($nodeId, $portsNeeded);
            if ($allocations) {
                $selectedNodeId = $nodeId;
                break;
            }
        }

        if (!$allocations || count($allocations) < 1) {
            throw new Exception('No available allocations found on any compatible node');
        }

        $primaryAllocation = $allocations[0];
        $additionalAllocations = array_slice($allocations, 1);

        $data = [
            'name' => $this->order_number . ' - ' . $product->name,
            'owner_id' => $this->user_id,
            'egg_id' => $product->egg_id,
            'node_id' => $selectedNodeId,
            'allocation_id' => $primaryAllocation['id'],
            'allocation_additional' => array_column($additionalAllocations, 'id'),
            'cpu' => $cpu,
            'memory' => $memory,
            'disk' => $disk,
            'swap' => $product->swap ?? 0,
            'io' => $product->io ?? 500,
            'environment' => $environment,
            'skip_scripts' => false,
            'start_on_completion' => true,
            'oom_killer' => false,
            'database_limit' => $product->databases ?? 0,
            'allocation_limit' => $portsNeeded,
            'backup_limit' => $product->backups ?? 0,
        ];

        $server = app(ServerCreationService::class)->handle($data);

        $this->update(['server_id' => $server->id]);

        return $server;
    }

    /**
     * Manually assign an existing server to this order
     */
    public function assignServer(Server $server): void
    {
        $this->update([
            'server_id' => $server->id,
            'notes' => trim($this->notes . "\n\n[" . now()->format('Y-m-d H:i') . "] Server manually assigned: " . $server->name),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'auto_renew' => false,
        ]);
    }

    public function refund(): void
    {
        $this->update([
            'status' => 'refunded',
            'auto_renew' => false,
        ]);
    }
}
