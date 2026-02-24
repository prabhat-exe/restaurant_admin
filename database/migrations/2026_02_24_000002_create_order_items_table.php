<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_id');
            $table->integer('item_id');
            $table->integer('user_id');
            $table->integer('store_id');
            $table->integer('attachment_id')->nullable();
            $table->string('attachment_url')->nullable();
            $table->string('item_type')->default(0); // 0=veg, 1=non-veg
            $table->string('category_name')->nullable();
            $table->string('menu_name')->nullable();
            $table->string('item_name');
            $table->decimal('price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->string('short_description')->nullable();
            $table->string('description')->nullable();
            $table->integer('status')->default(0); // 0=Inactive, 1=Active, 2=Delete
            $table->integer('order_status')->default(4); // 0=order_delay, 1=order_accepted, 2=processing, 3=ready_for_pickup, 4=new_order, 5=order_deliver
            $table->string('notes')->nullable();
            $table->integer('customize_status')->default(0); // 0=No, 1=Yes
            $table->integer('addon_status')->default(0); // 0=No, 1=Yes
            $table->integer('is_meal')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
