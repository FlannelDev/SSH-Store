<?php

use App\Plugins\ShadowStore\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const BOXES = [
        [
            'number' => 2,
            'slots' => 40,
            'price' => 20.00,
            'memory_mb' => 8192,
            'disk_mb' => 204800,
        ],
        [
            'number' => 3,
            'slots' => 72,
            'price' => 35.00,
            'memory_mb' => 12288,
            'disk_mb' => 307200,
        ],
        [
            'number' => 4,
            'slots' => 96,
            'price' => 45.00,
            'memory_mb' => 16384,
            'disk_mb' => 409600,
        ],
        [
            'number' => 5,
            'slots' => 104,
            'price' => 55.00,
            'memory_mb' => 20480,
            'disk_mb' => 512000,
        ],
        [
            'number' => 6,
            'slots' => 112,
            'price' => 70.00,
            'memory_mb' => 24576,
            'disk_mb' => 614400,
        ],
        [
            'number' => 7,
            'slots' => 120,
            'price' => 130.00,
            'memory_mb' => 32768,
            'disk_mb' => 716800,
        ],
        [
            'number' => 8,
            'slots' => 128,
            'price' => 170.00,
            'memory_mb' => 65536,
            'disk_mb' => 819200,
        ],
    ];

    public function up(): void
    {
        $templates = [
            'arma-reforger-standard' => [
                'name_prefix' => 'Arma Reforger - Shadow Box ',
                'sort_base' => 100,
            ],
            'arma-reforger-performance' => [
                'name_prefix' => 'Arma Reforger - Premium Shadow Box ',
                'sort_base' => 200,
            ],
        ];

        foreach ($templates as $sourceSlug => $meta) {
            $template = Product::query()->where('slug', $sourceSlug)->first();

            if (!$template) {
                continue;
            }

            foreach (self::BOXES as $index => $box) {
                Product::query()->updateOrCreate(
                    ['slug' => $sourceSlug . '-box-' . $box['number']],
                    [
                        'name' => $meta['name_prefix'] . $box['number'],
                        'description' => $template->description,
                        'features' => $template->features,
                        'image' => $template->image,
                        'category' => $template->category,
                        'game' => $template->game,
                        'egg_id' => $template->egg_id,
                        'node_ids' => $template->node_ids,
                        'excluded_node_ids' => $template->excluded_node_ids,
                        'billing_type' => 'monthly',
                        'base_price' => $box['price'],
                        'price_per_slot' => 0,
                        'min_slots' => $box['slots'],
                        'max_slots' => $box['slots'],
                        'slot_increment' => 1,
                        'default_slots' => $box['slots'],
                        'memory' => $box['memory_mb'],
                        'memory_per_slot' => null,
                        'disk' => $box['disk_mb'],
                        'disk_per_slot' => null,
                        'cpu' => $template->cpu,
                        'cpu_per_slot' => null,
                        'swap' => $template->swap,
                        'io' => $template->io,
                        'databases' => $template->databases,
                        'backups' => $template->backups,
                        'allocations' => $template->allocations,
                        'is_active' => true,
                        'is_featured' => $template->is_featured,
                        'sort_order' => $meta['sort_base'] + $index,
                        'stock' => $template->stock,
                    ]
                );
            }

            $template->forceFill([
                'is_active' => false,
                'sort_order' => $meta['sort_base'] - 1,
            ])->save();
        }
    }

    public function down(): void
    {
        Product::query()
            ->whereIn('slug', ['arma-reforger-standard', 'arma-reforger-performance'])
            ->update(['is_active' => true]);

        Product::query()
            ->where(function ($query) {
                foreach (['arma-reforger-standard-box-', 'arma-reforger-performance-box-'] as $prefix) {
                    $query->orWhere('slug', 'like', $prefix . '%');
                }
            })
            ->delete();
    }
};
