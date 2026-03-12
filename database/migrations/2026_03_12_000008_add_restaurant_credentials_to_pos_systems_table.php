<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_systems', function (Blueprint $table) {
            $table->text('restaurant_email')->nullable()->after('secret_key');
            $table->text('restaurant_password')->nullable()->after('restaurant_email');
        });
    }

    public function down(): void
    {
        Schema::table('pos_systems', function (Blueprint $table) {
            $table->dropColumn(['restaurant_email', 'restaurant_password']);
        });
    }
};
