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
        if (Schema::hasTable('product_variants')) {
            Schema::table('product_variants', function (Blueprint $table) {
                if (!Schema::hasColumn('product_variants', 'arch')) {
                    $table->string('arch')->nullable()->after('product_id');
                }
                if (!Schema::hasColumn('product_variants', 'size')) {
                    $table->string('size')->nullable()->after('arch');
                }
            });
        } else {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->string('arch')->nullable();
                $table->string('size')->nullable();
                $table->string('sku')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->decimal('selling_price', 10, 2)->default(0);
                $table->integer('quantity')->default(0);
                $table->string('image')->nullable();
                $table->boolean('status')->default(1);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('product_variants')) {
            Schema::table('product_variants', function (Blueprint $table) {
                if (Schema::hasColumn('product_variants', 'size')) {
                    $table->dropColumn('size');
                }
                if (Schema::hasColumn('product_variants', 'arch')) {
                    $table->dropColumn('arch');
                }
            });
        }
    }
};
