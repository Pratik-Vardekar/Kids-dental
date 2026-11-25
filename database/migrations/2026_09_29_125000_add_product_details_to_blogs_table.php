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
        Schema::table('blogs', function (Blueprint $table) {
            if (!Schema::hasColumn('blogs', 'subtitle')) {
                $table->string('subtitle')->nullable()->after('title');
            }
            if (!Schema::hasColumn('blogs', 'product')) {
                $table->string('product')->nullable()->after('subtitle');
            }
            if (!Schema::hasColumn('blogs', 'application')) {
                $table->string('application')->nullable()->after('product');
            }
            if (!Schema::hasColumn('blogs', 'key_benefit')) {
                $table->string('key_benefit')->nullable()->after('application');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['subtitle', 'product', 'application', 'key_benefit']);
        });
    }
};
