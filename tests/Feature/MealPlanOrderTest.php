<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MealPlanOrderTest extends TestCase
{
    use RefreshDatabase;

    private function createFixture(): array
    {
        $user = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'api_token' => 'test-token',
        ]);

        $restaurant = Restaurant::create([
            'name' => 'Meal Plan Cafe',
            'email' => 'meal-plan@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'latitude' => 28.6139000,
            'longitude' => 77.2090000,
            'delivery_radius_km' => 50,
        ]);

        $category = Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Meals',
        ]);

        $item = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'Balanced Bowl',
            'price' => 100,
            'is_available' => true,
        ]);

        return compact('user', 'restaurant', 'item');
    }

    private function mealPlanPayload(Restaurant $restaurant, MenuItem $item, int $totalDays = 20): array
    {
        $dates = [];
        $cursor = Carbon::parse('2026-04-28');

        while (count($dates) < $totalDays) {
            if (!$cursor->isWeekend()) {
                $dates[] = $cursor->format('Y-m-d');
            }
            $cursor->addDay();
        }

        return [
            'store_id' => $restaurant->id,
            'store_name' => $restaurant->name,
            'order_category' => 1,
            'order_type' => 1,
            'total_quantity' => $totalDays,
            'total_price' => $totalDays * 100,
            'total_tax' => 0,
            'payment_method' => 'upi',
            'delivery_address' => 'Connaught Place, Delhi',
            'address_lat' => 28.6304,
            'address_long' => 77.2177,
            'is_meal_plan' => true,
            'plan_type' => '20_day',
            'days_per_week' => 5,
            'total_plan_days' => $totalDays,
            'start_date' => '2026-04-28',
            'meal_slot_times' => ['Lunch' => '12:30 PM'],
            'meal_plan_summary' => ['total_days' => $totalDays, 'meals_per_day' => 1],
            'schedule' => collect($dates)->map(fn (string $date, int $index) => [
                'scheduled_date' => $date,
                'scheduled_time' => '12:30 PM',
                'plan_day_number' => $index + 1,
                'plan_week_number' => intdiv($index, 5) + 1,
                'meal_slot' => 'Lunch',
                'item_id' => $item->id,
                'item_name' => $item->name,
                'price' => 100,
                'total_price' => 100,
                'quantity' => 1,
            ])->all(),
        ];
    }

    private function itemOrderPayload(Restaurant $restaurant, MenuItem $item, string $selectedDate, string $time = '18:30'): array
    {
        return [
            'store_id' => $restaurant->id,
            'store_name' => $restaurant->name,
            'order_category' => 1,
            'order_type' => 1,
            'total_quantity' => 1,
            'total_price' => 100,
            'total_tax' => 0,
            'payment_method' => 'upi',
            'selectedDate' => $selectedDate,
            'time' => $time,
            'delivery_address' => 'Connaught Place, Delhi',
            'address_lat' => 28.6304,
            'address_long' => 77.2177,
            'is_meal_plan' => false,
            'items' => [[
                'item_id' => $item->id,
                'item_name' => $item->name,
                'price' => 100,
                'total_price' => 100,
                'quantity' => 1,
            ]],
        ];
    }

    public function test_unauthorized_checkout_is_rejected(): void
    {
        $fixture = $this->createFixture();

        $response = $this->postJson('/api/orders/place', $this->mealPlanPayload($fixture['restaurant'], $fixture['item']));

        $response->assertStatus(401);
    }

    public function test_meal_plan_checkout_creates_complete_order_and_day_wise_items(): void
    {
        Carbon::setTestNow('2026-04-24 10:00:00');
        $fixture = $this->createFixture();

        $response = $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/orders/place', $this->mealPlanPayload($fixture['restaurant'], $fixture['item']));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSame(1, Order::count());
        $this->assertSame(20, OrderItem::count());

        $order = Order::first();
        $this->assertTrue($order->is_meal_plan);
        $this->assertSame('2026-04-28', $order->plan_start_date->format('Y-m-d'));
        $this->assertSame('2026-05-25', $order->plan_end_date->format('Y-m-d'));

        $firstItem = OrderItem::orderBy('scheduled_date')->first();
        $lastItem = OrderItem::orderByDesc('scheduled_date')->first();

        $this->assertTrue($firstItem->is_meal_plan_item);
        $this->assertSame('2026-04-28', $firstItem->scheduled_date->format('Y-m-d'));
        $this->assertSame('2026-05-25', $lastItem->scheduled_date->format('Y-m-d'));
        $this->assertFalse(OrderItem::all()->contains(fn (OrderItem $item) => $item->scheduled_date->isWeekend()));

        Carbon::setTestNow();
    }

    public function test_future_orders_api_returns_upcoming_rows_for_logged_in_user(): void
    {
        Carbon::setTestNow('2026-04-24 10:00:00');
        $fixture = $this->createFixture();

        $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/orders/place', $this->mealPlanPayload($fixture['restaurant'], $fixture['item'], 2))
            ->assertOk();

        $response = $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->getJson('/api/orders/future');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('orders.0.date', '2026-04-28')
            ->assertJsonPath('orders.0.items.0.item_name', 'Balanced Bowl');

        Carbon::setTestNow();
    }

    public function test_meal_plan_future_orders_include_todays_scheduled_rows(): void
    {
        Carbon::setTestNow('2026-04-28 10:00:00');
        $fixture = $this->createFixture();

        $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/orders/place', $this->mealPlanPayload($fixture['restaurant'], $fixture['item'], 2))
            ->assertOk();

        $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->getJson('/api/orders/future?type=meal-plan')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('orders.0.date', '2026-04-28')
            ->assertJsonPath('orders.0.items.0.order_kind', 'meal_plan')
            ->assertJsonPath('orders.0.items.0.item_name', 'Balanced Bowl');

        Carbon::setTestNow();
    }

    public function test_pick_your_item_future_date_becomes_scheduled_item_order(): void
    {
        Carbon::setTestNow('2026-04-28 10:00:00');
        $fixture = $this->createFixture();

        $response = $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/orders/place', $this->itemOrderPayload($fixture['restaurant'], $fixture['item'], '2026-04-30'));

        $response->assertOk()->assertJson(['success' => true]);

        $order = Order::first();
        $this->assertFalse($order->is_meal_plan);
        $this->assertSame(1, (int) $order->pre_order_status);
        $this->assertSame('2026-04-30 18:30:00', $order->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertTrue($order->is_future_scheduled);

        $item = OrderItem::first();
        $this->assertFalse($item->is_meal_plan_item);
        $this->assertSame('2026-04-30', $item->scheduled_date->format('Y-m-d'));

        $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->getJson('/api/orders/future?type=item')
            ->assertOk()
            ->assertJsonPath('orders.0.items.0.order_kind', 'scheduled_item')
            ->assertJsonPath('orders.0.items.0.item_name', 'Balanced Bowl');

        $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->getJson('/api/orders/future?type=meal-plan')
            ->assertOk()
            ->assertJsonPath('orders', []);

        Carbon::setTestNow();
    }

    public function test_pick_your_item_today_requires_thirty_minute_delivery_lead_time(): void
    {
        Carbon::setTestNow('2026-04-28 10:00:00');
        $fixture = $this->createFixture();

        $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/orders/place', $this->itemOrderPayload($fixture['restaurant'], $fixture['item'], '2026-04-28', '10:20'))
            ->assertStatus(422)
            ->assertJsonPath('errors.0', 'Delivery time must be at least 30 minutes from now');

        $this->assertSame(0, Order::count());

        $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/orders/place', $this->itemOrderPayload($fixture['restaurant'], $fixture['item'], '2026-04-28', '10:30'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, Order::count());

        Carbon::setTestNow();
    }
}
