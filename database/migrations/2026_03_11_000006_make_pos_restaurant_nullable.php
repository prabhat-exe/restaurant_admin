<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_systems', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restaurant_id');
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pos_systems', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restaurant_id');
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
        });
    }
};
