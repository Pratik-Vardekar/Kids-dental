<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('help_categories')) {
            return;
        }

        $now = now();
        $categories = [
            'Bioflx Crowns',
            'Kids-e-Crown®',
            'Kids-e-Restore',
            'Kids-e-SSC',
            'e-Sdf®',
            'e-Pit And Fissure Sealant',
            'e-Space Maintainer',
            'e-MTA®',
            'e-MTA Putty',
            'e-MTA Sealer',
            'e-MTA Carrier',
            'Kids-e-Files',
        ];

        $colors = ['yellow', 'teal', 'purple', 'orange'];
        $order = 1;

        foreach ($categories as $catTitle) {
            $exists = DB::table('help_categories')->where('title', $catTitle)->exists();
            if (!$exists) {
                DB::table('help_categories')->insert([
                    'title' => $catTitle,
                    'color' => $colors[($order - 1) % 4],
                    'sort_order' => $order,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $order++;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe not to delete to preserve user data
    }
};
