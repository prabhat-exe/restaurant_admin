<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_code', 8)->nullable()->after('email');
            $table->string('phone_number', 20)->nullable()->unique()->after('phone_code');
            $table->string('first_name')->nullable()->after('phone_number');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('api_token', 100)->nullable()->unique()->after('password');
            $table->string('otp_code')->nullable()->after('api_token');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->boolean('is_phone_verified')->default(false)->after('otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone_code',
                'phone_number',
                'first_name',
                'last_name',
                'api_token',
                'otp_code',
                'otp_expires_at',
                'is_phone_verified',
            ]);
        });
    }
};
