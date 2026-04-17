<?php

namespace App\Plugins\ShadowStore\Services;

use App\Models\Server;
use App\Models\Allocation;
use App\Plugins\ShadowStore\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProvisioningService
{
    public function provision(Order $order): ?Server
    {
        if (!config('shadow-store.auto_provision')) {
            Log::info("Auto-provisioning disabled, skipping order {$order->order_number}");
            return null;
        }

        try {
            $product = $order->product;
            $tier = $order->tier;
            
            // Get resources from tier or product
            $memory = $tier?->memory ?? $product->memory;
            // Enforce a fixed 1TB allocation for all provisioned servers.
            $disk = 1000000;
            $cpu = $tier?->cpu ?? $product->cpu;
            $databases = $tier?->databases ?? $product->databases;
            $backups = $tier?->backups ?? $product->backups;
            $allocations = $tier?->allocations ?? $product->allocations;
            
            // Find available allocation on the target node
            $allocation = Allocation::where('node_id', $product->node_id)
                ->whereNull('server_id')
                ->first();
                
            if (!$allocation) {
                Log::error("No available allocation for order {$order->order_number}");
                return null;
            }

            // Create the server
            $server = Server::create([
                'uuid' => Str::uuid(),
                'owner_id' => $order->user_id,
                'node_id' => $product->node_id,
                'allocation_id' => $allocation->id,
                'egg_id' => $product->egg_id,
                'nest_id' => $product->nest_id,
                'name' => $order->user->username . "'s " . $product->name,
                'description' => "Order: {$order->order_number}",
                'memory' => $memory,
                'disk' => $disk,
                'cpu' => $cpu,
                'database_limit' => $databases,
                'backup_limit' => $backups,
                'allocation_limit' => $allocations,
                'startup' => $product->egg?->startup ?? '',
                'image' => $product->egg?->docker_images[0] ?? '',
                'status' => null,
            ]);

            // Update allocation
            $allocation->update(['server_id' => $server->id]);

            // Update order with server ID and expiration
            $expiresAt = match($order->billing_period) {
                'quarterly' => now()->addMonths(3),
                'yearly' => now()->addYear(),
                default => now()->addMonth(),
            };

            $order->update([
                'server_id' => $server->id,
                'expires_at' => $expiresAt,
            ]);

            Log::info("Server provisioned for order {$order->order_number}: Server ID {$server->id}");
            
            return $server;
        } catch (\Exception $e) {
            Log::error("Provisioning failed for order {$order->order_number}: " . $e->getMessage());
            return null;
        }
    }
}
