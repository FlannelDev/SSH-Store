<?php

namespace App\Plugins\ShadowStore\Models;

use Illuminate\Database\Eloquent\Model;

class DedicatedMachine extends Model
{
    protected $table = 'store_dedicated_machines';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cpu_model',
        'cpu_type',
        'cpu_cores',
        'cpu_threads',
        'cpu_speed',
        'cpu_score',
        'ram_gb',
        'ram_type',
        'storage_config',
        'storage_total_gb',
        'storage_type',
        'network_speed',
        'bandwidth_tb',
        'ip_addresses',
        'cost_price',
        'sell_price',
        'setup_fee',
        'provider',
        'provider_sku',
        'datacenter_location',
        'is_active',
        'is_featured',
        'rapid_deploy',
        'setup_time_hours',
        'stock',
        'sort_order',
    ];

    protected $casts = [
        'cpu_speed' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'rapid_deploy' => 'boolean',
    ];

    public function getPriceDisplayAttribute(): string
    {
        return '$' . number_format($this->sell_price, 2) . '/mo';
    }

    public function getMarkupPercentAttribute(): float
    {
        if ($this->cost_price <= 0) return 0;
        return round((($this->sell_price - $this->cost_price) / $this->cost_price) * 100, 1);
    }

    public function getProfitAttribute(): float
    {
        return $this->sell_price - $this->cost_price;
    }

    /**
     * Check if this machine is suitable for gaming workloads
     */
    public function isSuitableForGaming(): bool
    {
        return in_array($this->cpu_type, ['gaming', 'workstation']);
    }
}
