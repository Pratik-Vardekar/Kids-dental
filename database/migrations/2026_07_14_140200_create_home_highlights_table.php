<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_highlights', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();
        });

        $now = now();

        DB::table('home_highlights')->insert([
            [
                'title' => 'Safe Toys - Happy Kids',
                'icon' => 'frontend/assets/images/sc_toys.svg',
                'sort_order' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Creative Play - Smarter Learning',
                'icon' => 'frontend/assets/images/sc_rainbow.svg',
                'sort_order' => 2,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Exciting Offers Every Week',
                'icon' => 'frontend/assets/images/sc_gift.svg',
                'sort_order' => 3,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Fast Delivery Across India',
                'icon' => 'frontend/assets/images/sc_tracking.svg',
                'sort_order' => 4,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Let the Fun Begin',
                'icon' => 'frontend/assets/images/sc_party-popper.svg',
                'sort_order' => 5,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('home_highlights');
    }
};
