<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE pos_systems MODIFY menu_url TEXT NOT NULL');
        DB::statement('ALTER TABLE pos_systems MODIFY client_id TEXT NOT NULL');
        DB::statement('ALTER TABLE pos_systems MODIFY public_key TEXT NOT NULL');
        DB::statement('ALTER TABLE pos_systems MODIFY secret_key TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pos_systems MODIFY menu_url VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE pos_systems MODIFY client_id VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE pos_systems MODIFY public_key VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE pos_systems MODIFY secret_key TEXT NOT NULL');
    }
};
