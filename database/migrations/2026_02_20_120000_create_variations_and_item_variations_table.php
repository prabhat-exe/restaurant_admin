<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');
            $table->string('variation_name');
            $table->boolean('created_by_super_admin')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->timestamps();
        });

        Schema::create('item_variations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('variation_id');
            $table->decimal('variation_price', 10, 2);
            $table->decimal('web_price', 10, 2);
            $table->decimal('mobile_price', 10, 2);
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('menu_items')->onDelete('cascade');
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_variations');
        Schema::dropIfExists('variations');
    }
};
