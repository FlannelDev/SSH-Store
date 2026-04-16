<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use ShhStore\Models\StoreCategory;
use ShhStore\Models\StoreProduct;

class ShhStoreSeeder extends Seeder
{
    public function run(): void
    {
        $armaReforger = StoreCategory::updateOrCreate(
            ['slug' => 'arma-reforger'],
            [
                'name' => 'Arma Reforger',
                'description' => 'High-performance Arma Reforger server hosting on Ryzen 9 9950X3D with 3D V-Cache.',
                'icon' => 'heroicon-o-server-stack',
                'sort_order' => 1,
                'is_visible' => true,
            ]
        );

        $standardBoxes = [
            ['name' => 'Arma Reforger - Shadow Box 2', 'cpu' => '4x CPU @ 4.8-6GHz', 'ram' => '8 GB DDR5',  'storage' => '200 GB NVMe', 'price' => 20.00,  'sort' => 1],
            ['name' => 'Arma Reforger - Shadow Box 3', 'cpu' => '4x CPU @ 4.8-6GHz', 'ram' => '12 GB DDR5', 'storage' => '300 GB NVMe', 'price' => 35.00,  'sort' => 2],
            ['name' => 'Arma Reforger - Shadow Box 4', 'cpu' => '4x CPU @ 4.8-6GHz', 'ram' => '16 GB DDR5', 'storage' => '400 GB NVMe', 'price' => 45.00,  'sort' => 3],
            ['name' => 'Arma Reforger - Shadow Box 5', 'cpu' => '4x CPU @ 4.8-6GHz', 'ram' => '20 GB DDR5', 'storage' => '500 GB NVMe', 'price' => 55.00,  'sort' => 4],
            ['name' => 'Arma Reforger - Shadow Box 6', 'cpu' => '4x CPU @ 4.8-6GHz', 'ram' => '24 GB DDR5', 'storage' => '600 GB NVMe', 'price' => 70.00,  'sort' => 5],
            ['name' => 'Arma Reforger - Shadow Box 7', 'cpu' => '4x CPU @ 4.8-6GHz', 'ram' => '32 GB DDR5', 'storage' => '700 GB NVMe', 'price' => 130.00, 'sort' => 6],
            ['name' => 'Arma Reforger - Shadow Box 8', 'cpu' => '4x CPU @ 4.8-6GHz', 'ram' => '64 GB DDR5', 'storage' => '800 GB NVMe', 'price' => 170.00, 'sort' => 7],
        ];

        foreach ($standardBoxes as $box) {
            StoreProduct::updateOrCreate(
                ['slug' => Str::slug($box['name'])],
                [
                    'category_id' => $armaReforger->id,
                    'name' => $box['name'],
                    'description' => 'High-performance Arma Reforger server hosting on Ryzen 9 9950X3D with 3D V-Cache. No overselling, no compromises.',
                    'tier' => 'Standard',
                    'cpu' => $box['cpu'],
                    'ram' => $box['ram'],
                    'storage' => $box['storage'],
                    'price_monthly' => $box['price'],
                    'is_featured' => true,
                    'is_visible' => true,
                    'in_stock' => true,
                    'sort_order' => $box['sort'],
                    'features' => [
                        'Platform' => 'Ryzen 9 9950X3D',
                        'Memory' => 'DDR5 ECC',
                        'DDoS Protection' => 'Included',
                        'Backups' => 'Daily',
                        'Control Panel' => 'Full Access',
                        'Support' => '24/7 Discord',
                    ],
                ]
            );
        }

        $premiumBoxes = [
            ['name' => 'Arma Reforger - Premium Shadow Box 2', 'cpu' => '5x CPU @ 4.8-6GHz', 'ram' => '8 GB DDR5',  'storage' => '200 GB NVMe', 'price' => 20.00,  'sort' => 8],
            ['name' => 'Arma Reforger - Premium Shadow Box 3', 'cpu' => '5x CPU @ 4.8-6GHz', 'ram' => '12 GB DDR5', 'storage' => '300 GB NVMe', 'price' => 35.00,  'sort' => 9],
            ['name' => 'Arma Reforger - Premium Shadow Box 4', 'cpu' => '5x CPU @ 4.8-6GHz', 'ram' => '16 GB DDR5', 'storage' => '400 GB NVMe', 'price' => 45.00,  'sort' => 10],
            ['name' => 'Arma Reforger - Premium Shadow Box 5', 'cpu' => '5x CPU @ 4.8-6GHz', 'ram' => '20 GB DDR5', 'storage' => '500 GB NVMe', 'price' => 55.00,  'sort' => 11],
            ['name' => 'Arma Reforger - Premium Shadow Box 6', 'cpu' => '5x CPU @ 4.8-6GHz', 'ram' => '24 GB DDR5', 'storage' => '600 GB NVMe', 'price' => 70.00,  'sort' => 12],
            ['name' => 'Arma Reforger - Premium Shadow Box 7', 'cpu' => '5x CPU @ 4.8-6GHz', 'ram' => '32 GB DDR5', 'storage' => '700 GB NVMe', 'price' => 130.00, 'sort' => 13],
            ['name' => 'Arma Reforger - Premium Shadow Box 8', 'cpu' => '5x CPU @ 4.8-6GHz', 'ram' => '64 GB DDR5', 'storage' => '800 GB NVMe', 'price' => 170.00, 'sort' => 14],
        ];

        foreach ($premiumBoxes as $box) {
            StoreProduct::updateOrCreate(
                ['slug' => Str::slug($box['name'])],
                [
                    'category_id' => $armaReforger->id,
                    'name' => $box['name'],
                    'description' => 'Premium Arma Reforger hosting with lower node density and priority CPU scheduling. Built for AI-heavy operations and large-scale PvP.',
                    'tier' => 'Premium',
                    'cpu' => $box['cpu'],
                    'ram' => $box['ram'],
                    'storage' => $box['storage'],
                    'price_monthly' => $box['price'],
                    'is_featured' => true,
                    'is_visible' => true,
                    'in_stock' => true,
                    'sort_order' => $box['sort'],
                    'features' => [
                        'Platform' => 'Ryzen 9 9950X3D',
                        'Memory' => 'DDR5 ECC',
                        'Node Density' => 'Lower density',
                        'CPU Priority' => 'Priority scheduling',
                        'DDoS Protection' => 'Included',
                        'Backups' => 'Daily',
                        'Control Panel' => 'Full Access',
                        'Support' => '24/7 Discord',
                    ],
                ]
            );
        }
    }
}
