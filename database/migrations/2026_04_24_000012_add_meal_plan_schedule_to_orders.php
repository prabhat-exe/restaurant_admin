<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_meal_plan')->default(false)->after('order_comments');
            $table->string('plan_type')->nullable()->after('is_meal_plan');
            $table->date('plan_start_date')->nullable()->after('plan_type');
            $table->date('plan_end_date')->nullable()->after('plan_start_date');
            $table->integer('days_per_week')->nullable()->after('plan_end_date');
            $table->integer('plan_total_days')->nullable()->after('days_per_week');
            $table->json('meal_plan_summary_json')->nullable()->after('plan_total_days');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_meal_plan_item')->default(false)->after('is_meal');
            $table->date('scheduled_date')->nullable()->after('is_meal_plan_item');
            $table->time('scheduled_time')->nullable()->after('scheduled_date');
            $table->integer('plan_day_number')->nullable()->after('scheduled_time');
            $table->integer('plan_week_number')->nullable()->after('plan_day_number');
            $table->string('meal_slot')->nullable()->after('plan_week_number');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'is_meal_plan_item',
                'scheduled_date',
                'scheduled_time',
                'plan_day_number',
                'plan_week_number',
                'meal_slot',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_meal_plan',
                'plan_type',
                'plan_start_date',
                'plan_end_date',
                'days_per_week',
                'plan_total_days',
                'meal_plan_summary_json',
            ]);
        });
    }
};
