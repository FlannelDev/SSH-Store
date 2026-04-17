<?php

use App\Plugins\ShadowStore\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const STORAGE_BY_BOX = [
        2 => 204800,
        3 => 307200,
        4 => 409600,
        5 => 512000,
        6 => 614400,
        7 => 716800,
        8 => 819200,
    ];

    public function up(): void
    {
        foreach (self::STORAGE_BY_BOX as $boxNumber => $diskMb) {
            foreach (['arma-reforger-standard-box-', 'arma-reforger-performance-box-'] as $slugPrefix) {
                Product::query()
                    ->where('slug', $slugPrefix . $boxNumber)
                    ->update(['disk' => $diskMb]);
            }
        }
    }

    public function down(): void
    {
        foreach ([5, 6, 7, 8] as $boxNumber) {
            foreach (['arma-reforger-standard-box-', 'arma-reforger-performance-box-'] as $slugPrefix) {
                Product::query()
                    ->where('slug', $slugPrefix . $boxNumber)
                    ->update(['disk' => 409600]);
            }
        }
    }
};