<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\ItemAddon;
use App\Models\ItemVariation;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\SubCategory;
use App\Models\TaxClass;
use App\Models\TaxRate;
use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;

class RestaurantMenuApiController extends Controller
{
    public function restaurants()
    {
        $restaurants = Restaurant::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'logo', 'is_active']);

        return response()->json([
            'success' => true,
            'data' => $restaurants,
        ]);
    }

    public function menu($restaurantId)
    {
        $restaurant = Restaurant::query()
            ->where('id', $restaurantId)
            ->where('is_active', 1)
            ->first();

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'errors' => ['Restaurant not found or inactive'],
            ], 404);
        }

        $categories = Category::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('status', 1)
            ->orderBy('id')
            ->get();

        $subCategories = SubCategory::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('status', 1)
            ->orderBy('id')
            ->get()
            ->groupBy('category_id');

        $items = MenuItem::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_available', 1)
            ->orderBy('id')
            ->get()
            ->groupBy('category_id');

        $allItems = $items->flatten();
        $itemIds = $allItems->pluck('id')->all();

        $itemVariations = ItemVariation::query()
            ->whereIn('item_id', $itemIds)
            ->get()
            ->groupBy('item_id');

        $variationIds = $itemVariations->flatten()->pluck('variation_id')->unique()->all();
        $variationsById = Variation::query()
            ->whereIn('id', $variationIds)
            ->get()
            ->keyBy('id');

        $itemAddons = ItemAddon::query()
            ->whereIn('item_id', $itemIds)
            ->get()
            ->groupBy('item_id');

        $addonItemIds = $itemAddons->flatten()->pluck('addon_item_id')->unique()->all();
        $addonItemsById = MenuItem::query()
            ->whereIn('id', $addonItemIds)
            ->get()
            ->keyBy('id');

        $taxClassIds = $subCategories->flatten()->pluck('tax_class_id')->filter()->unique()->all();
        $taxClassById = TaxClass::query()
            ->whereIn('id', $taxClassIds)
            ->get()
            ->keyBy('id');
        $taxRates = TaxRate::query()
            ->whereIn('tax_class_id', $taxClassIds)
            ->get()
            ->groupBy('tax_class_id');

        $categoryData = [];

        foreach ($categories as $category) {
            $categoryItems = $items->get($category->id, collect());
            $categorySubCategories = $subCategories->get($category->id, collect());

            // Current schema does not map an item directly to sub_category.
            // We use first sub-category tax info for the whole category.
            $selectedSubCategory = $categorySubCategories->first();
            $taxClass = $selectedSubCategory && $selectedSubCategory->tax_class_id
                ? $taxClassById->get($selectedSubCategory->tax_class_id)
                : null;
            $rates = $taxClass ? $taxRates->get($taxClass->id, collect()) : collect();

            $taxClassPayload = $taxClass ? [[
                'id' => $taxClass->id,
                'tax_class_name' => $taxClass->tax_class_name,
                'type' => $taxClass->type,
            ]] : [];

            $mapTaxClass = $taxClass ? [[
                'tax_class_id' => $taxClass->id,
                'tax_class_name' => $taxClass->tax_class_name,
                'type' => $taxClass->type,
                'tax_rate' => $rates->map(function ($rate) use ($taxClass) {
                    return [
                        'tax_name' => $rate->tax_name,
                        'tax_amount' => (float) $rate->tax_amount,
                        'type' => $taxClass->type,
                    ];
                })->values()->all(),
            ]] : [];

            $itemData = $categoryItems->map(function ($item) use ($itemVariations, $variationsById, $itemAddons, $addonItemsById) {
                $variations = ($itemVariations->get($item->id, collect()))->map(function ($iv) use ($variationsById) {
                    $variation = $variationsById->get($iv->variation_id);
                    return [
                        'variation_id' => $iv->variation_id,
                        'variation_name' => $variation->variation_name ?? 'Variation',
                        'variation_price' => (float) $iv->web_price,
                    ];
                })->values()->all();

                $addons = ($itemAddons->get($item->id, collect()))->map(function ($addonMap) use ($addonItemsById) {
                    $addonItem = $addonItemsById->get($addonMap->addon_item_id);
                    return [
                        'addon_id' => $addonMap->addon_item_id,
                        'name' => $addonItem->name ?? 'Addon',
                        'price' => (float) $addonMap->web_price,
                    ];
                })->values()->all();

                return [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description ?? '',
                    'price' => (float) $item->price,
                    'image' => $item->image,
                    'is_veg' => true,
                    'variation_status' => count($variations) > 0 ? 1 : 0,
                    'variations' => $variations,
                    'addons_status' => count($addons) > 0 ? 1 : 0,
                    'addons' => $addons,
                ];
            })->values()->all();

            $categoryData[] = [
                'category_id' => $category->id,
                'name' => $category->name,
                'sub_category_data' => [[
                    'menu_id' => $category->id,
                    'name' => $category->name,
                    'tax_class' => $taxClassPayload,
                    'map_tax_class' => $mapTaxClass,
                    'item_data' => $itemData,
                ]],
            ];
        }

        return response()->json([
            'success' => true,
            'restaurant_id' => (int) $restaurant->id,
            'restaurant_name' => $restaurant->name,
            'category_data' => $categoryData,
        ]);
    }

    public function triggerReindex(Request $request, $restaurantId)
    {
        $restaurant = Restaurant::query()
            ->where('id', $restaurantId)
            ->where('is_active', 1)
            ->first();

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'errors' => ['Restaurant not found or inactive'],
            ], 404);
        }

        $aiBaseUrl = rtrim((string) config('services.ai_service.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8000')), '/');

        try {
            $response = Http::timeout(15)->post($aiBaseUrl . '/reindex', [
                'restaurant_id' => (int) $restaurant->id,
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['AI reindex request failed'],
                    'status' => $response->status(),
                    'body' => $response->json(),
                ], 502);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reindex triggered successfully',
                'data' => $response->json(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }
}
