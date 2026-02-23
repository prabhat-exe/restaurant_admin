<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_addons', function (Blueprint $table) {
            $table->id();

            // Main Item
            $table->unsignedBigInteger('item_id');

            // Addon Item (also from menu_items)
            $table->unsignedBigInteger('addon_item_id');

            $table->unsignedBigInteger('user_id');

            $table->decimal('pos_price', 10, 2)->default(0);
            $table->decimal('web_price', 10, 2)->default(0);
            $table->decimal('mobile_price', 10, 2)->default(0);

            $table->timestamps();

            // Prevent duplicate addon mapping
            $table->unique(['item_id', 'addon_item_id']);

            // Foreign Keys (Recommended)
            $table->foreign('item_id')
                  ->references('id')
                  ->on('menu_items')
                  ->onDelete('cascade');

            $table->foreign('addon_item_id')
                  ->references('id')
                  ->on('menu_items')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_addons');
    }
};