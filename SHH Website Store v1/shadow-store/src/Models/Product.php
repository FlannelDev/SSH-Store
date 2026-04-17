<?php

namespace App\Plugins\ShadowStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Egg;
use App\Models\Node;

class Product extends Model
{
    protected const ARMA_REFORGER_PRESET_TIERS = [
        ['label' => 'Shadow Box 2', 'price' => 20],
        ['label' => 'Shadow Box 3', 'price' => 35],
        ['label' => 'Shadow Box 4', 'price' => 45],
        ['label' => 'Shadow Box 5', 'price' => 55],
        ['label' => 'Shadow Box 6', 'price' => 70],
        ['label' => 'Shadow Box 7', 'price' => 130],
        ['label' => 'Shadow Box 8', 'price' => 170],
    ];

    protected $table = 'store_products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'features',
        'image',
        'image_asset_id',
        'category',
        'game',
        'egg_id',
        'node_ids',
        'excluded_node_ids',
        'billing_type',
        'base_price',
        'price_per_slot',
        'min_slots',
        'max_slots',
        'slot_increment',
        'default_slots',
        'memory',
        'memory_per_slot',
        'disk',
        'disk_per_slot',
        'cpu',
        'cpu_per_slot',
        'swap',
        'io',
        'databases',
        'backups',
        'allocations',
        'is_active',
        'is_featured',
        'sort_order',
        'stock',
    ];

    protected $casts = [
        'features' => 'array',
        'node_ids' => 'array',
        'excluded_node_ids' => 'array',
        'base_price' => 'decimal:2',
        'price_per_slot' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function egg(): BelongsTo
    {
        return $this->belongsTo(Egg::class);
    }

    public function imageAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_asset_id');
    }

    /**
     * Get available nodes for this product
     */
    public function getAvailableNodes()
    {
        $query = Node::query();
        
        // If specific nodes are set, only use those
        if (!empty($this->node_ids)) {
            $query->whereIn('id', $this->node_ids);
        }
        
        // Exclude any excluded nodes
        if (!empty($this->excluded_node_ids)) {
            $query->whereNotIn('id', $this->excluded_node_ids);
        }
        
        return $query->get();
    }

    /**
     * Get egg variables that need user input
     */
    public function getUserEditableVariables()
    {
        if (!$this->egg) {
            return collect();
        }
        
        return $this->egg->variables()
            ->where('user_editable', true)
            ->orderBy('sort')
            ->get();
    }

    /**
     * Get all egg variables with defaults
     */
    public function getAllVariables()
    {
        if (!$this->egg) {
            return collect();
        }
        
        return $this->egg->variables()->orderBy('sort')->get();
    }

    public function calculatePrice(int $slots): float
    {
        if ($this->billing_type === 'slots') {
            return $slots * (float) $this->price_per_slot;
        }
        return (float) $this->base_price;
    }

    public function calculateMemory(int $slots): int
    {
        if ($this->memory_per_slot) {
            return $slots * $this->memory_per_slot;
        }
        return $this->memory ?? 0;
    }

    public function calculateCpu(int $slots): int
    {
        if ($this->cpu_per_slot) {
            return $slots * $this->cpu_per_slot;
        }
        return $this->cpu ?? 0;
    }

    public function calculateDisk(int $slots): int
    {
        if ($this->disk_per_slot) {
            return $slots * $this->disk_per_slot;
        }
        return $this->disk ?? 0;
    }

    public function getPriceDisplayAttribute(): string
    {
        if ($this->billing_type === 'slots') {
            return '$' . number_format($this->price_per_slot, 2) . '/slot';
        }
        return '$' . number_format($this->base_price, 2) . '/mo';
    }

    public function getResolvedImageUrlAttribute(): ?string
    {
        if ($this->imageAsset) {
            return $this->imageAsset->public_url;
        }

        if (blank($this->image)) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return asset('storage/' . ltrim($this->image, '/'));
    }

    public function usesPresetBoxTiers(): bool
    {
        return $this->billing_type === 'slots'
            && $this->game === 'arma-reforger'
            && in_array($this->slug, ['arma-reforger-standard', 'arma-reforger-performance'], true);
    }

    public function getAdminPricingModelAttribute(): string
    {
        if ($this->usesPresetBoxTiers()) {
            return 'Shadow Box Presets';
        }

        return match ($this->billing_type) {
            'slots' => 'Per-Slot Pricing',
            'monthly' => 'Fixed Monthly',
            'onetime' => 'One-Time Purchase',
            default => ucfirst((string) $this->billing_type),
        };
    }

    public function getAdminPriceDisplayAttribute(): string
    {
        if ($this->usesPresetBoxTiers()) {
            $prices = array_column(self::ARMA_REFORGER_PRESET_TIERS, 'price');

            return '$' . number_format(min($prices), 2) . ' - $' . number_format(max($prices), 2) . '/mo';
        }

        if ($this->billing_type === 'slots') {
            return '$' . number_format((float) $this->price_per_slot, 2) . '/slot';
        }

        $suffix = $this->billing_type === 'onetime' ? '' : '/mo';

        return '$' . number_format((float) $this->base_price, 2) . $suffix;
    }

    public function getAdminTierSummaryAttribute(): string
    {
        if ($this->usesPresetBoxTiers()) {
            $labels = array_column(self::ARMA_REFORGER_PRESET_TIERS, 'label');

            return implode(', ', $labels);
        }

        return '-';
    }
}
