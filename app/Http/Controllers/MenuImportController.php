<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\SubCategory;
use App\Models\ItemVariation;
use App\Models\TaxClass;
use App\Models\TaxRate;




class MenuImportController extends Controller
{
    public function import(Request $request)
    {
        $json = json_decode($request->json_data, true);

        DB::transaction(function () use ($json) {

            $restaurant = auth('restaurant')->user();

            if (!empty($json['store_data'])) {

                $storeData = $json['store_data'];
                // dd($storeData);
                $restaurant->update([
                    // 'name' => $storeData['store_name'] ?? $restaurant->name,
                    // 'phone' => $storeData['phone_number'] ?? $restaurant->phone,
                    'razorpay_account_id' => $storeData['razorpay_account_id'] ?? null,
                    'store_type' => $storeData['store_type'] ?? null,
                    'store_url' => $storeData['store_url'] ?? null,
                    'short_description' => $storeData['short_description'] ?? null,
                    'description' => $storeData['description'] ?? null,
                    'address' => $storeData['address'] ?? null,
                    'latitude' => $storeData['lattitude'] ?? null,
                    'longitude' => $storeData['longitude'] ?? null,
                    'country_currency' => $storeData['country_currency'] ?? null,
                    'country_id' => $storeData['country_id'] ?? null,
                    'postal_code' => $storeData['postal_code'] ?? null,
                    'cook_time' => $storeData['cook_time'] ?? null,
                    'rating' => $storeData['rating'] ?? null,
                    'rating_count' => $storeData['rating_count'] ?? null,
                    'is_active' => $storeData['status'] ?? 1,
                    'country_id' => $storeData['country_id'] ?? null,
                ]);
            }

            if(!empty($json['Category_data'])) {
            foreach ($json['Category_data'] as $categoryData) {

                // Create Category
                $category = Category::create([
                    'restaurant_id' => $restaurant->id,
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'] ?? null,
                    'status' => $categoryData['status']
                ]);

                foreach ($categoryData['sub_category_data'] as $subCategoryData) {

                    /* HANDLE TAX CLASS FIRST */

                    $taxClassId = null;

                    if (!empty($subCategoryData['tax_class'])) {

                        foreach ($subCategoryData['tax_class'] as $taxClassData) {

                            // Check existing tax class
                            $taxClass = TaxClass::where('restaurant_id', $restaurant->id)
                                ->where('tax_class_name', $taxClassData['tax_class_name'])
                                ->first();

                            if (!$taxClass) {
                                $taxClass = TaxClass::create([
                                    'restaurant_id' => $restaurant->id,
                                    'tax_class_name' => $taxClassData['tax_class_name'],
                                    'type' => $taxClassData['type']
                                ]);
                            }

                            $taxClassId = $taxClass->id;

                            /* HANDLE TAX RATES (NO DUPLICATE) */

                            if (!empty($subCategoryData['map_tax_class'])) {

                                foreach ($subCategoryData['map_tax_class'] as $mapTax) {

                                    if (!empty($mapTax['tax_rate'][0])) {

                                        $rateData = $mapTax['tax_rate'][0];

                                        $existingRate = TaxRate::where('tax_class_id', $taxClass->id)
                                            ->where('tax_name', $rateData['tax_name'])
                                            ->first();

                                        if (!$existingRate) {
                                            TaxRate::create([
                                                'tax_class_id' => $taxClass->id,
                                                'tax_name' => $rateData['tax_name'],
                                                'tax_amount' => $rateData['tax_amount']
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    /*CREATE SUB CATEGORY */

                    $subCategory = SubCategory::create([
                        'restaurant_id' => $restaurant->id,
                        'category_id' => $category->id,
                        'name' => $subCategoryData['name'],
                        'description' => $subCategoryData['description'] ?? null,
                        'status' => $subCategoryData['status'],
                        'tax_class_id' => $taxClassId
                    ]);

                    /* CREATE ITEMS */

                    foreach ($subCategoryData['item_data'] as $itemData) {

                        // Handle image
                        $imageUrl = null;
                        // dd($itemData['item_attachment']);
                        if (!empty($itemData['item_attachment'])) {
                            $imageUrl =
                                $itemData['item_attachment']['base_url'] .
                                $itemData['item_attachment']['attachment_url'];
                        }
                        // dd($imageUrl);
                        $item = MenuItem::create([
                            'restaurant_id' => $restaurant->id,
                            'category_id' => $category->id,
                            'name' => $itemData['name'],
                            'description' => $itemData['description'],
                            'price' => $itemData['web_price'] ?? 0,
                            'is_available' => $itemData['status'],
                            'image' => $imageUrl ,
                        ]);

                        /* HANDLE VARIATIONS */

                        if (!empty($itemData['item_variation'])) {

                            foreach ($itemData['item_variation'] as $variation) {

                                if (!empty($variation['variation_detail'][0])) {

                                    ItemVariation::create([
                                        'menu_item_id' => $item->id,
                                        'variation_name' => $variation['variation_detail'][0]['variation_name'],
                                        'price' => $variation['variation_price']
                                    ]);
                                }
                            }
                        }
                    }
                }
            }}else{
                return back()->with('error', 'No Category Data Found in JSON');
            }
        });

        return back()->with('success', 'Menu Imported Successfully');
    }

    public function dashboard()
    {
        $restaurant = auth('restaurant')->user();

        $items = MenuItem::where('restaurant_id', $restaurant->id)
            ->latest()
            ->paginate(20);

        return view('restaurant.dashboard', compact('items'));
    }

}
