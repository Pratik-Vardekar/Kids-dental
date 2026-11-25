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
        if (!Schema::hasTable('distributor_enquiries')) {
            Schema::create('distributor_enquiries', function (Blueprint $table) {
                $table->id();
                $table->string('company_name');
                $table->string('contact_person');
                $table->string('email');
                $table->string('mobile_no', 50);
                $table->string('country_city');
                $table->text('message')->nullable();
                $table->boolean('status')->default(false); // 0: Unread/Pending, 1: Read/Contacted
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributor_enquiries');
    }
};
