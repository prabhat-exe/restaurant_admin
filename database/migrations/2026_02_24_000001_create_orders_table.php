<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('order_item_id');
            $table->string('order_id')->unique();
            $table->integer('order_number')->nullable();
            $table->integer('item_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('store_id');
            $table->string('payment_intent_id')->nullable();
            $table->string('razorpay_order_id')->nullable();
            $table->integer('token_number')->nullable();
            $table->integer('table_no')->default(0);
            $table->integer('store_status')->default(1);
            $table->integer('print_status')->default(0);
            $table->string('delivery_address')->nullable();
            $table->string('address_lat')->nullable();
            $table->string('address_long')->nullable();
            $table->decimal('delivery_charges', 10, 2)->default(0);
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->string('selectedDate')->nullable();
            $table->string('time')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('store_name')->nullable();
            $table->integer('order_status')->default(4);
            $table->integer('asap_status')->default(0);
            $table->integer('pre_order_status')->nullable();
            $table->string('house_no')->nullable();
            $table->string('street_name')->nullable();
            $table->string('zip_code')->nullable();
            $table->integer('order_type')->default(1);
            $table->integer('order_category')->default(1);
            $table->integer('total_quantity')->default(1);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->decimal('total_tax', 10, 2)->default(0);
            $table->decimal('cgst', 10, 2)->default(0);
            $table->decimal('sgst', 10, 2)->default(0);
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('remaning_amount', 10, 2)->default(0);
            $table->string('near_land_mark')->nullable();
            $table->string('complete_address')->nullable();
            $table->string('distance')->nullable();
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('cash_discount', 10, 2)->default(0);
            $table->decimal('cash_discount_percentage', 10, 2)->default(0);
            $table->decimal('tip', 10, 2)->default(0);
            $table->decimal('round_off', 10, 2)->default(0);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->string('order_note')->nullable();
            $table->decimal('commission', 10, 2)->default(0);
            $table->integer('is_cancel')->default(0);
            $table->string('cancel_reason')->nullable();
            $table->string('device_type')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('upi_method')->nullable();
            $table->integer('payment_response')->default(0);
            $table->integer('firebase_order_update')->default(0);
            $table->integer('email_confirmation')->default(0);
            $table->string('order_comments')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
