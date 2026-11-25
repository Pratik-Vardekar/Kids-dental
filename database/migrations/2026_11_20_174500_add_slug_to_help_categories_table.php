<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('help_categories') && !Schema::hasColumn('help_categories', 'slug')) {
            Schema::table('help_categories', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('title');
            });

            // Populate slugs for all existing categories
            $categories = DB::table('help_categories')->get();
            foreach ($categories as $cat) {
                DB::table('help_categories')->where('id', $cat->id)->update([
                    'slug' => Str::slug($cat->title),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('help_categories') && Schema::hasColumn('help_categories', 'slug')) {
            Schema::table('help_categories', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
