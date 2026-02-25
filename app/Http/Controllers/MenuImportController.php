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
use App\Models\Variation;
use App\Models\ItemAddon;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MenuExcelImport;



class MenuImportController extends Controller
{
    // public function import(Request $request)
    // {
    //     $json = json_decode($request->json_data, true);

    //     DB::transaction(function () use ($json) {

    //         $restaurant = auth('restaurant')->user();

    //         if (!empty($json['store_data'])) {

    //             $storeData = $json['store_data'];
    //             // dd($storeData);
    //             $restaurant->update([
    //                 // 'name' => $storeData['store_name'] ?? $restaurant->name,
    //                 // 'phone' => $storeData['phone_number'] ?? $restaurant->phone,
    //                 'razorpay_account_id' => $storeData['razorpay_account_id'] ?? null,
    //                 'store_type' => $storeData['store_type'] ?? null,
    //                 'store_url' => $storeData['store_url'] ?? null,
    //                 'short_description' => $storeData['short_description'] ?? null,
    //                 'description' => $storeData['description'] ?? null,
    //                 'address' => $storeData['address'] ?? null,
    //                 'latitude' => $storeData['lattitude'] ?? null,
    //                 'longitude' => $storeData['longitude'] ?? null,
    //                 'country_currency' => $storeData['country_currency'] ?? null,
    //                 'country_id' => $storeData['country_id'] ?? null,
    //                 'postal_code' => $storeData['postal_code'] ?? null,
    //                 'cook_time' => $storeData['cook_time'] ?? null,
    //                 'rating' => $storeData['rating'] ?? null,
    //                 'rating_count' => $storeData['rating_count'] ?? null,
    //                 'is_active' => $storeData['status'] ?? 1,
    //                 'country_id' => $storeData['country_id'] ?? null,
    //             ]);
    //         }

    //         if(!empty($json['Category_data'])) {
    //         foreach ($json['Category_data'] as $categoryData) {

    //             // Create Category
    //             $category = Category::create([
    //                 'restaurant_id' => $restaurant->id,
    //                 'name' => $categoryData['name'],
    //                 'description' => $categoryData['description'] ?? null,
    //                 'status' => $categoryData['status']
    //             ]);

    //             foreach ($categoryData['sub_category_data'] as $subCategoryData) {

    //                 /* HANDLE TAX CLASS FIRST */

    //                 $taxClassId = null;

    //                 if (!empty($subCategoryData['tax_class'])) {

    //                     foreach ($subCategoryData['tax_class'] as $taxClassData) {

    //                         // Check existing tax class
    //                         $taxClass = TaxClass::where('restaurant_id', $restaurant->id)
    //                             ->where('tax_class_name', $taxClassData['tax_class_name'])
    //                             ->first();

    //                         if (!$taxClass) {
    //                             $taxClass = TaxClass::create([
    //                                 'restaurant_id' => $restaurant->id,
    //                                 'tax_class_name' => $taxClassData['tax_class_name'],
    //                                 'type' => $taxClassData['type']
    //                             ]);
    //                         }

    //                         $taxClassId = $taxClass->id;

    //                         /* HANDLE TAX RATES (NO DUPLICATE) */

    //                         if (!empty($subCategoryData['map_tax_class'])) {

    //                             foreach ($subCategoryData['map_tax_class'] as $mapTax) {

    //                                 if (!empty($mapTax['tax_rate'][0])) {

    //                                     $rateData = $mapTax['tax_rate'][0];

    //                                     $existingRate = TaxRate::where('tax_class_id', $taxClass->id)
    //                                         ->where('tax_name', $rateData['tax_name'])
    //                                         ->first();

    //                                     if (!$existingRate) {
    //                                         TaxRate::create([
    //                                             'tax_class_id' => $taxClass->id,
    //                                             'tax_name' => $rateData['tax_name'],
    //                                             'tax_amount' => $rateData['tax_amount']
    //                                         ]);
    //                                     }
    //                                 }
    //                             }
    //                         }
    //                     }
    //                 }

    //                 /*CREATE SUB CATEGORY */

    //                 $subCategory = SubCategory::create([
    //                     'restaurant_id' => $restaurant->id,
    //                     'category_id' => $category->id,
    //                     'name' => $subCategoryData['name'],
    //                     'description' => $subCategoryData['description'] ?? null,
    //                     'status' => $subCategoryData['status'],
    //                     'tax_class_id' => $taxClassId
    //                 ]);

    //                 /* CREATE ITEMS */

    //                 foreach ($subCategoryData['item_data'] as $itemData) {

    //                     // Handle image
    //                     $imageUrl = null;
    //                     // dd($itemData['item_attachment']);
    //                     if (!empty($itemData['item_attachment'])) {
    //                         $imageUrl =
    //                             $itemData['item_attachment']['base_url'] .
    //                             $itemData['item_attachment']['attachment_url'];
    //                     }
    //                     // dd($imageUrl);
    //                     $item = MenuItem::create([
    //                         'restaurant_id' => $restaurant->id,
    //                         'category_id' => $category->id,
    //                         'name' => $itemData['name'],
    //                         'description' => $itemData['description'],
    //                         'price' => $itemData['web_price'] ?? 0,
    //                         'is_available' => $itemData['status'],
    //                         'image' => $imageUrl ,
    //                     ]);

    //                     /* HANDLE VARIATIONS */

    //                     // if (!empty($itemData['item_variation'])) {

    //                     //     foreach ($itemData['item_variation'] as $variation) {

    //                     //         if (!empty($variation['variation_detail'][0])) {

    //                     //             ItemVariation::create([
    //                     //                 'menu_item_id' => $item->id,
    //                     //                 'variation_name' => $variation['variation_detail'][0]['variation_name'],
    //                     //                 'price' => $variation['variation_price']
    //                     //             ]);
    //                     //         }
    //                     //     }
    //                     // }
    //                     if (!empty($itemData['item_variation'])) {

    //                         foreach ($itemData['item_variation'] as $variationData) {

    //                             if (!empty($variationData['variation_detail'][0])) {

    //                                 $variationName = trim($variationData['variation_detail'][0]['variation_name']);

    //                                 // Prices from JSON
    //                                 $posPrice = $variationData['variation_price'] ?? 0;
    //                                 $webPrice = $variationData['web_price'] ?? 0;
    //                                 $mobilePrice = $variationData['mobile_price'] ?? 0;

    //                                 $variation = Variation::firstOrCreate(
    //                                     [
    //                                         'variation_name' => $variationName,
    //                                         'created_by' => $restaurant->id
    //                                     ],
    //                                     [
    //                                         'created_by_super_admin' => 0,
    //                                         'department_id' => null
    //                                     ]
    //                                 );

    //                                 ItemVariation::updateOrCreate(
    //                                     [
    //                                         'item_id' => $item->id,
    //                                         'variation_id' => $variation->id,
    //                                     ],
    //                                     [
    //                                         'user_id' => $restaurant->id,
    //                                         'pos_price' => $posPrice,         // variation_price
    //                                         'web_price' => $webPrice,         // web_price from JSON
    //                                         'mobile_price' => $mobilePrice,   // mobile_price from JSON
    //                                     ]
    //                                 );
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //         /*
    //         |--------------------------------------------------------------------------
    //         | SECOND PASS - HANDLE ADDONS
    //         |--------------------------------------------------------------------------
    //         */

    //         foreach ($json['Category_data'] as $categoryData) {

    //             foreach ($categoryData['sub_category_data'] as $subCategoryData) {

    //                 foreach ($subCategoryData['item_data'] as $itemData) {

    //                     // Find main item
    //                     $mainItem = MenuItem::where('restaurant_id', $restaurant->id)
    //                         ->where('name', $itemData['name'])
    //                         ->first();

    //                     if (!$mainItem) continue;

    //                     if (!empty($itemData['addon_data'])) {

    //                         foreach ($itemData['addon_data'] as $addonData) {

    //                             $addonItemName = $addonData['addon_item']['name'] ?? null;

    //                             if (!$addonItemName) continue;

    //                             $addonItem = MenuItem::where('restaurant_id', $restaurant->id)
    //                                 ->where('name', $addonItemName)
    //                                 ->first();

    //                             if ($addonItem) {

    //                                 ItemAddon::updateOrCreate(
    //                                     [
    //                                         'item_id' => $mainItem->id,
    //                                         'addon_item_id' => $addonItem->id,
    //                                     ],
    //                                     [
    //                                         'user_id' => $restaurant->id,
    //                                         'pos_price' => $addonData['offered_price'] ?? 0,
    //                                         'web_price' => $addonData['web_offered_price'] ?? 0,
    //                                         'mobile_price' => $addonData['mob_offered_price'] ?? 0,
    //                                     ]
    //                                 );
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //         }else{
    //             // dd('hello');
    //             return back()->with('error', 'No Category Data Found in JSON');
    //         }
    //     });
    //     // dd('hello');
    //     return redirect()->route('restaurant.dashboard')->with('success', 'Menu imported successfully');
    // }

    public function import(Request $request)
    {
        $restaurant = auth('restaurant')->user();

        // -----------------------------
        // 1️⃣ EXCEL IMPORT
        // -----------------------------
        if ($request->hasFile('excel_file')) {

            $request->validate([
                'excel_file' => 'required|mimes:xlsx,xls|max:4096'
            ]);

            DB::transaction(function () use ($request, $restaurant) {
                Excel::import(
                    new MenuExcelImport($restaurant),
                    $request->file('excel_file')
                );
            });

            return redirect()
                ->route('restaurant.dashboard')
                ->with('success', 'Excel menu imported successfully');
        }

        // -----------------------------
        // 2️⃣ JSON IMPORT
        // -----------------------------
        if ($request->filled('json_data')) {

            $json = json_decode($request->json_data, true);

            if (!$json) {
                return back()->with('error', 'Invalid JSON format');
            }

            DB::transaction(function () use ($json, $restaurant) {

                // -----------------------------
                // YOUR EXISTING JSON LOGIC
                // -----------------------------

                if (!empty($json['store_data'])) {

                    $storeData = $json['store_data'];

                    $restaurant->update([
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
                    ]);
                }

                if (empty($json['Category_data'])) {
                    throw new \Exception('No Category Data Found in JSON');
                }

                foreach ($json['Category_data'] as $categoryData) {

                    $category = \App\Models\Category::create([
                        'restaurant_id' => $restaurant->id,
                        'name' => $categoryData['name'],
                        'description' => $categoryData['description'] ?? null,
                        'status' => $categoryData['status']
                    ]);

                    foreach ($categoryData['sub_category_data'] as $subCategoryData) {

                        $taxClassId = null;

                        if (!empty($subCategoryData['tax_class'])) {

                            foreach ($subCategoryData['tax_class'] as $taxClassData) {

                                $taxClass = \App\Models\TaxClass::firstOrCreate(
                                    [
                                        'restaurant_id' => $restaurant->id,
                                        'tax_class_name' => $taxClassData['tax_class_name']
                                    ],
                                    [
                                        'type' => $taxClassData['type']
                                    ]
                                );

                                $taxClassId = $taxClass->id;
                            }
                        }

                        $subCategory = \App\Models\SubCategory::create([
                            'restaurant_id' => $restaurant->id,
                            'category_id' => $category->id,
                            'name' => $subCategoryData['name'],
                            'description' => $subCategoryData['description'] ?? null,
                            'status' => $subCategoryData['status'],
                            'tax_class_id' => $taxClassId
                        ]);

                        foreach ($subCategoryData['item_data'] as $itemData) {

                            $imageUrl = null;

                            if (!empty($itemData['item_attachment'])) {
                                $imageUrl =
                                    $itemData['item_attachment']['base_url'] .
                                    $itemData['item_attachment']['attachment_url'];
                            }

                            $item = \App\Models\MenuItem::create([
                                'restaurant_id' => $restaurant->id,
                                'category_id' => $category->id,
                                'name' => $itemData['name'],
                                'description' => $itemData['description'],
                                'price' => $itemData['web_price'] ?? 0,
                                'is_available' => $itemData['status'],
                                'image' => $imageUrl,
                            ]);
                        }
                    }
                }
            });

            return redirect()
                ->route('restaurant.dashboard')
                ->with('success', 'JSON menu imported successfully');
        }

        return back()->with('error', 'Please paste JSON or upload Excel file');
    }

    public function dashboard()
    {
        $restaurant = auth('restaurant')->user();

        $items = MenuItem::with([
                'variations.variation',
                'addons.addonItem'
            ])
            ->where('restaurant_id', $restaurant->id)
            ->latest()
            ->paginate(20);

        $hasMenu = $items->total() > 0;
        // dd($items);
        return view('restaurant.dashboard', compact('items', 'hasMenu'));
    }

}
