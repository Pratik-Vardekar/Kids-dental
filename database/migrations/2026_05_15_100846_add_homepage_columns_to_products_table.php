<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->string('thumbnail')->nullable()->after('slug');

            $table->boolean('is_trending')
                ->default(0)
                ->after('status');

            $table->boolean('is_featured')
                ->default(0)
                ->after('is_trending');

            $table->boolean('is_best_selling')
                ->default(0)
                ->after('is_featured');

            $table->boolean('is_top_rated')
                ->default(0)
                ->after('is_best_selling');

            $table->boolean('is_new_arrival')
                ->default(0)
                ->after('is_top_rated');

            $table->string('badge')
                ->nullable()
                ->after('is_new_arrival');

            $table->integer('discount')
                ->default(0)
                ->after('badge');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->dropColumn([
                'thumbnail',
                'is_trending',
                'is_featured',
                'is_best_selling',
                'is_top_rated',
                'is_new_arrival',
                'badge',
                'discount'
            ]);

        });
    }
};