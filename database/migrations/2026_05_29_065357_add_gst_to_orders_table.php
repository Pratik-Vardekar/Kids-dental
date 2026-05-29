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
    Schema::table('orders', function (Blueprint $table) {
        // $table->decimal('gst_rate', 5, 2)->default(18);
        // $table->decimal('cgst_amount', 10, 2)->default(0);
        // $table->decimal('sgst_amount', 10, 2)->default(0);
        // $table->decimal('igst_amount', 10, 2)->default(0);
        $table->boolean('is_interstate')->default(false);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
