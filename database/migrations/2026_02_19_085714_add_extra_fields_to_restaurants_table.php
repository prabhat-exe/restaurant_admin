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
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('razorpay_account_id')->nullable();
            $table->integer('store_type')->nullable();
            $table->string('store_url')->nullable();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('country_currency')->nullable();
            $table->integer('country_id')->nullable();
            $table->string('postal_code')->nullable();
            $table->integer('cook_time')->nullable();
            $table->decimal('rating', 3,2)->nullable();
            $table->integer('rating_count')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            //
        });
    }
};
