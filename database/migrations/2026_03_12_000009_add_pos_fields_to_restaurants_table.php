<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('is_pos')->default(false)->after('is_active');
            $table->text('menu_url')->nullable()->after('is_pos');
            $table->text('client_id')->nullable()->after('menu_url');
            $table->text('public_key')->nullable()->after('client_id');
            $table->text('secret_key')->nullable()->after('public_key');
            $table->timestamp('last_synced_at')->nullable()->after('secret_key');
            $table->string('last_sync_status')->nullable()->after('last_synced_at');
            $table->text('last_sync_error')->nullable()->after('last_sync_status');

            $table->index(['is_pos', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropIndex(['is_pos', 'is_active']);
            $table->dropColumn([
                'is_pos',
                'menu_url',
                'client_id',
                'public_key',
                'secret_key',
                'last_synced_at',
                'last_sync_status',
                'last_sync_error',
            ]);
        });
    }
};
