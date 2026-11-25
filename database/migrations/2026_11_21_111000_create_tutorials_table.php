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
        if (!Schema::hasTable('tutorials')) {
            Schema::create('tutorials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sub_category_id')->constrained('sub_categories')->onDelete('cascade');
                $table->string('title');
                $table->text('video_url')->nullable();
                $table->string('video_file')->nullable();
                $table->string('video_id')->nullable();
                $table->string('thumbnail')->nullable();
                $table->longText('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->tinyInteger('status')->default(1)->comment('1: Active, 0: Inactive');
                $table->timestamps();

                $table->index(['sub_category_id', 'status', 'sort_order']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutorials');
    }
};
