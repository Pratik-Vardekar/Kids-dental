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
        if (Schema::hasTable('help_faqs')) {
            Schema::table('help_faqs', function (Blueprint $table) {
                if (!Schema::hasColumn('help_faqs', 'sub_category_id')) {
                    $table->unsignedBigInteger('sub_category_id')->nullable()->after('id');
                }
            });

            // If sub_categories table exists, try to map existing help_faqs to matching sub_categories by name
            if (Schema::hasTable('sub_categories') && Schema::hasTable('help_categories')) {
                $faqs = \Illuminate\Support\Facades\DB::table('help_faqs')
                    ->whereNull('sub_category_id')
                    ->whereNotNull('help_category_id')
                    ->get();

                foreach ($faqs as $faq) {
                    $helpCat = \Illuminate\Support\Facades\DB::table('help_categories')->find($faq->help_category_id);
                    if ($helpCat) {
                        $subCat = \Illuminate\Support\Facades\DB::table('sub_categories')
                            ->where('name', 'like', '%' . $helpCat->title . '%')
                            ->orWhere('slug', \Illuminate\Support\Str::slug($helpCat->title))
                            ->first();

                        if ($subCat) {
                            \Illuminate\Support\Facades\DB::table('help_faqs')
                                ->where('id', $faq->id)
                                ->update(['sub_category_id' => $subCat->id]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('help_faqs') && Schema::hasColumn('help_faqs', 'sub_category_id')) {
            Schema::table('help_faqs', function (Blueprint $table) {
                $table->dropColumn('sub_category_id');
            });
        }
    }
};
