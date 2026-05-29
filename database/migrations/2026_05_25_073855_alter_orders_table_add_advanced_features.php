<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->string('invoice_no')->nullable()->after('order_id');

            $table->decimal('paid_amount', 10, 2)->default(0)->after('grand_total');

            $table->decimal('due_amount', 10, 2)->default(0)->after('paid_amount');

            $table->enum('payment_type', ['full', 'partial'])
                ->default('full')
                ->after('payment_method');

            $table->date('invoice_date')->nullable();

            $table->date('due_date')->nullable();

            $table->string('tracking_id')->nullable();

            $table->string('courier_name')->nullable();

            $table->string('tracking_url')->nullable();

            $table->text('admin_note')->nullable();

            $table->timestamp('delivered_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();

            $table->timestamp('shipped_at')->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->dropColumn([
                'invoice_no',
                'paid_amount',
                'due_amount',
                'payment_type',
                'invoice_date',
                'due_date',
                'tracking_id',
                'courier_name',
                'tracking_url',
                'admin_note',
                'delivered_at',
                'cancelled_at',
                'shipped_at'
            ]);

        });
    }
};