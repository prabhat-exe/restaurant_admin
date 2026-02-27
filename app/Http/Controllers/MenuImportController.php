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
use Illuminate\Support\Facades\Http;



class MenuImportController extends Controller
{
    public function showImportForm()
    {
        $restaurant = auth('restaurant')->user();
        $hasMenu = MenuItem::where('restaurant_id', $restaurant->id)->exists();

        return view('restaurant.menu_import', compact('hasMenu'));
    }

    
    public function import(Request $request)
    {
        $restaurant = auth('restaurant')->user();
        $hasMenu = MenuItem::where('restaurant_id', $restaurant->id)->exists();

        if ($hasMenu) {
            return back()->with('error', 'Menu already exists. Delete current menu before importing again.');
        }

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

            $this->triggerReindex($restaurant->id);

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

            $this->triggerReindex($restaurant->id);

            return redirect()
                ->route('restaurant.dashboard')
                ->with('success', 'JSON menu imported successfully');
        }

        return back()->with('error', 'Please paste JSON or upload Excel file');
    }

    public function destroyMenu(Request $request)
    {
        $restaurant = auth('restaurant')->user();

        $request->validate([
            'confirm_text' => ['required', 'in:DELETE'],
        ], [
            'confirm_text.in' => 'Type DELETE to confirm menu deletion.',
        ]);

        DB::transaction(function () use ($restaurant) {
            ItemAddon::where('user_id', $restaurant->id)->delete();
            ItemVariation::where('user_id', $restaurant->id)->delete();
            MenuItem::where('restaurant_id', $restaurant->id)->delete();
            SubCategory::where('restaurant_id', $restaurant->id)->delete();
            Category::where('restaurant_id', $restaurant->id)->delete();
            TaxClass::where('restaurant_id', $restaurant->id)->delete();
            Variation::where('created_by', $restaurant->id)->delete();
        });

        return redirect()
            ->route('menu.import.form')
            ->with('success', 'Entire menu deleted permanently. You can import a new menu now.');
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

    private function triggerReindex(int $restaurantId): void
    {
        $aiBaseUrl = rtrim((string) env('AI_SERVICE_URL', 'http://127.0.0.1:8000'), '/');

        try {
            Http::timeout(10)->post($aiBaseUrl . '/reindex', [
                'restaurant_id' => $restaurantId,
            ]);
        } catch (\Throwable $e) {
            // Do not block admin menu import on AI indexing issues.
            logger()->warning('AI reindex trigger failed', [
                'restaurant_id' => $restaurantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

}
