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
    Schema::create('products', function (Blueprint $table) {

        $table->id();

        $table->foreignId('category_id')
            ->constrained()
            ->onDelete('cascade');

        $table->string('name');

        $table->string('slug')->unique();

        $table->double('price', 10, 2)->default(0);

        $table->double('selling_price', 10, 2)->default(0);

        $table->longText('details')->nullable();

        $table->longText('description')->nullable();

        $table->longText('specification')->nullable();

        $table->integer('quantity')->default(0);

        $table->text('gallery')->nullable();

        $table->tinyInteger('status')->default(1);

        $table->timestamps();

    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
