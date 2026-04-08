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
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Models\Restaurant;



class MenuImportController extends Controller
{
    public function showImportForm()
    {
        $restaurant = auth('restaurant')->user();
        $hasMenu = MenuItem::where('restaurant_id', $restaurant->id)->exists();

        return view('restaurant.menu_import', compact('hasMenu'));
    }

    private function sendPdfToAi($pdfFile, int $restaurantId)
    {
        $aiBaseUrl = rtrim((string) env('AI_SERVICE_URL'), '/');

        $response = \Illuminate\Support\Facades\Http::timeout(120)
            ->attach(
                'file',
                file_get_contents($pdfFile->getRealPath()),
                $pdfFile->getClientOriginalName()
            )
            ->post($aiBaseUrl . '/parse-menu-pdf', [
                'restaurant_id' => $restaurantId
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    private function storeMenuFromJson(array $json, $restaurant)
    {
        foreach ($json['Category_data'] as $categoryData) {

            $category = \App\Models\Category::create([
                'restaurant_id' => $restaurant->id,
                'name' => $categoryData['name'],
                'status' => 1
            ]);

            foreach ($categoryData['sub_category_data'] as $subCategoryData) {

                $subCategory = \App\Models\SubCategory::create([
                    'restaurant_id' => $restaurant->id,
                    'category_id' => $category->id,
                    'name' => $subCategoryData['name'],
                    'status' => 1
                ]);

                foreach ($subCategoryData['item_data'] as $itemData) {

                    \App\Models\MenuItem::create([
                        'restaurant_id' => $restaurant->id,
                        'category_id' => $category->id,
                        'name' => $itemData['name'],
                        'description' => $itemData['description'] ?? null,
                        'price' => $itemData['price'] ?? 0,
                        'is_available' => 1,
                    ]);
                }
            }
        }
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
                ->with('success', 'Excel menu imported successfully. Please be patient while embedding is generating in background.');
        }

        // -----------------------------
        // 2️⃣ JSON IMPORT
        // -----------------------------
        if ($request->filled('json_data')) {

            $json = json_decode($request->json_data, true);

            if (!$json) {
                return back()->with('error', 'Invalid JSON format');
            }

            $importedItems = DB::transaction(function () use ($json, $restaurant) {
                $itemCount = 0;

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
                        'cook_time' => $this->normalizeCookTime($storeData['cook_time'] ?? null),
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

                            \App\Models\MenuItem::create([
                                'restaurant_id' => $restaurant->id,
                                'category_id' => $category->id,
                                'name' => $itemData['name'],
                                'description' => $itemData['description'],
                                'price' => $itemData['web_price'] ?? 0,
                                'is_available' => $this->normalizeItemAvailability($itemData),
                                'image' => $imageUrl,
                            ]);
                            $itemCount++;
                        }
                    }
                }

                return $itemCount;
            });

            $this->triggerReindex($restaurant->id);

            return redirect()
                ->route('restaurant.dashboard')
                ->with('success', "JSON menu imported successfully ({$importedItems} items. Please be patient while embedding is generating in background ).");
        }
        
        // -----------------------------
        // 3️⃣ PDF IMPORT (AI Based)
        // -----------------------------
        // if ($request->hasFile('pdf_file')) {
        //     // dd($request->all());

        //     // dd('hello to check the pdf upload file ');
        //     $request->validate([
        //         'pdf_file' => 'required|mimes:pdf|max:10240',
        //     ]);

        //     $restaurant = auth('restaurant')->user();

        //     try {

        //         // Send PDF to AI service
        //         $structuredJson = $this->sendPdfToAi(
        //             $request->file('pdf_file'),
        //             $restaurant->id
        //         );

        //         // Validate AI response
        //         if (
        //             !$structuredJson ||
        //             !isset($structuredJson['Category_data']) ||
        //             !is_array($structuredJson['Category_data'])
        //         ) {
        //             return back()->with('error', 'AI failed to extract valid menu data from PDF.');
        //         }

        //         // Store menu using your existing JSON logic
        //         DB::transaction(function () use ($structuredJson, $restaurant) {
        //             $this->storeMenuFromJson($structuredJson, $restaurant);
        //         });

        //         // Trigger AI reindex
        //         $this->triggerReindex($restaurant->id);

        //         return redirect()
        //             ->route('restaurant.dashboard')
        //             ->with('success', 'PDF menu imported successfully');

        //     } catch (\Throwable $e) {

        //         logger()->error('PDF import failed', [
        //             'restaurant_id' => $restaurant->id,
        //             'error' => $e->getMessage(),
        //         ]);

        //         return back()->with('error', 'Something went wrong while importing PDF.');
        //     }
        // }

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

        // Remove all AI artifacts for this restaurant (menu.json, cleaned/enriched, embeddings, etc.).
        $this->triggerPurge((int) $restaurant->id);

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

    public function delivery()
    {
        /** @var Restaurant $restaurant */
        $restaurant = auth('restaurant')->user();

        return view('restaurant.delivery', compact('restaurant'));
    }

    public function updateDeliverySettings(Request $request)
    {
        /** @var Restaurant $restaurant */
        $restaurant = auth('restaurant')->user();

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'delivery_radius_km' => ['required', 'numeric', 'min:0.1', 'max:200'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $updatePayload = [
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'address' => $validated['address'] ?? $restaurant->address,
        ];

        if (Schema::hasColumn('restaurants', 'delivery_radius_km')) {
            $updatePayload['delivery_radius_km'] = round((float) $validated['delivery_radius_km'], 2);
        }

        $restaurant->update($updatePayload);

        if (!Schema::hasColumn('restaurants', 'delivery_radius_km')) {
            return redirect()
                ->route('restaurant.delivery')
                ->with('error', 'Latitude and longitude were saved, but delivery radius needs a database migration before it can be stored. Run: php artisan migrate');
        }

        return redirect()
            ->route('restaurant.delivery')
            ->with('success', 'Delivery area updated successfully.');
    }

    public function pos()
    {
        return view('restaurant.pos');
    }

    public function createItem()
    {
        $restaurant = auth('restaurant')->user();

        $categories = Category::query()
            ->where('restaurant_id', $restaurant->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $addonItems = MenuItem::query()
            ->where('restaurant_id', $restaurant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'price']);

        return view('restaurant.item_form', [
            'item' => null,
            'categories' => $categories,
            'addonItems' => $addonItems,
            'existingVariations' => collect(),
            'existingAddons' => collect(),
        ]);
    }

    public function storeItem(Request $request)
    {
        $restaurant = auth('restaurant')->user();
        $validated = $this->validateItemPayload($request, $restaurant->id);

        DB::transaction(function () use ($validated, $restaurant) {
            $item = MenuItem::create([
                'restaurant_id' => $restaurant->id,
                'category_id' => (int) $validated['category_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'] ?? 0,
                'is_available' => (bool) ($validated['is_available'] ?? true),
                'image' => $validated['image'] ?? null,
            ]);

            $this->syncItemRelations($validated, $restaurant->id, $item);
        });

        $this->triggerReindex($restaurant->id);

        return redirect()
            ->route('restaurant.dashboard')
            ->with('success', 'Item added successfully.');
    }

    public function editItem(MenuItem $item)
    {
        $restaurant = auth('restaurant')->user();
        abort_if((int) $item->restaurant_id !== (int) $restaurant->id, 403);

        $item->load([
            'variations.variation',
            'addons.addonItem',
        ]);

        $categories = Category::query()
            ->where('restaurant_id', $restaurant->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $addonItems = MenuItem::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('id', '!=', $item->id)
            ->orderBy('name')
            ->get(['id', 'name', 'price']);

        return view('restaurant.item_form', [
            'item' => $item,
            'categories' => $categories,
            'addonItems' => $addonItems,
            'existingVariations' => $item->variations,
            'existingAddons' => $item->addons,
        ]);
    }

    public function updateItem(Request $request, MenuItem $item)
    {
        $restaurant = auth('restaurant')->user();
        abort_if((int) $item->restaurant_id !== (int) $restaurant->id, 403);

        $validated = $this->validateItemPayload($request, $restaurant->id);

        DB::transaction(function () use ($validated, $restaurant, $item) {
            $item->update([
                'category_id' => (int) $validated['category_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'] ?? 0,
                'is_available' => (bool) ($validated['is_available'] ?? true),
                'image' => $validated['image'] ?? null,
            ]);

            ItemVariation::where('item_id', $item->id)->delete();
            ItemAddon::where('item_id', $item->id)->delete();

            $this->syncItemRelations($validated, $restaurant->id, $item);
        });

        $this->triggerReindex($restaurant->id);

        return redirect()
            ->route('restaurant.dashboard')
            ->with('success', 'Item updated successfully.');
    }

    public function destroyItem(MenuItem $item)
    {
        $restaurant = auth('restaurant')->user();
        abort_if((int) $item->restaurant_id !== (int) $restaurant->id, 403);

        DB::transaction(function () use ($item) {
            ItemAddon::where('item_id', $item->id)->delete();
            ItemAddon::where('addon_item_id', $item->id)->delete();
            ItemVariation::where('item_id', $item->id)->delete();
            $item->delete();
        });

        $this->triggerReindex((int) $restaurant->id);

        return redirect()
            ->route('restaurant.dashboard')
            ->with('success', 'Item deleted successfully.');
    }

    private function triggerReindex(int $restaurantId): void
    {
        $aiBaseUrl = rtrim((string) env('AI_SERVICE_URL', 'http://127.0.0.1:8000'), '/');
        $payload = ['restaurant_id' => $restaurantId];

        // Run AI sync after sending HTTP response so menu edit/delete feels instant in UI.
        dispatch(function () use ($aiBaseUrl, $payload, $restaurantId) {
            try {
                Http::connectTimeout(3)
                    ->timeout(45)
                    ->post($aiBaseUrl . '/sync', $payload);
            } catch (\Throwable $e) {
                logger()->warning('AI sync trigger failed', [
                    'restaurant_id' => $restaurantId,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    private function triggerPurge(int $restaurantId): void
    {
        $aiBaseUrl = rtrim((string) env('AI_SERVICE_URL', 'http://127.0.0.1:8000'), '/');
        $payload = ['restaurant_id' => $restaurantId];

        dispatch(function () use ($aiBaseUrl, $payload, $restaurantId) {
            try {
                Http::connectTimeout(3)
                    ->timeout(45)
                    ->post($aiBaseUrl . '/purge', $payload);
            } catch (\Throwable $e) {
                logger()->warning('AI purge trigger failed', [
                    'restaurant_id' => $restaurantId,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    private function normalizeCookTime(mixed $cookTime): ?int
    {
        if ($cookTime === null || $cookTime === '') {
            return null;
        }

        if (is_int($cookTime)) {
            return $cookTime >= 0 ? $cookTime : null;
        }

        if (is_numeric($cookTime)) {
            $minutes = (int) $cookTime;
            return $minutes >= 0 ? $minutes : null;
        }

        if (is_string($cookTime) && preg_match('/\d+/', $cookTime, $matches)) {
            $minutes = (int) $matches[0];
            return $minutes >= 0 ? $minutes : null;
        }

        return null;
    }

    private function normalizeItemAvailability(array $itemData): bool
    {
        $status = $itemData['status'] ?? null;

        if (is_string($status)) {
            $normalized = strtolower(trim($status));

            if (in_array($normalized, ['active', 'enabled', 'available', 'open'], true)) {
                return true;
            }

            if (in_array($normalized, ['inactive', 'disabled', 'unavailable', 'closed'], true)) {
                return false;
            }
        }

        if ($status === true || $status === 1 || $status === '1') {
            return true;
        }

        if ($status === false || $status === 0 || $status === '0' || $status === null || $status === '') {
            foreach (['web_enable', 'pos_enable', 'mobile_enable'] as $flag) {
                $value = $itemData[$flag] ?? null;
                if ($value === true || $value === 1 || $value === '1') {
                    return true;
                }
            }

            return false;
        }

        return (bool) $status;
    }

    private function validateItemPayload(Request $request, int $restaurantId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(function ($query) use ($restaurantId) {
                    $query->where('restaurant_id', $restaurantId);
                }),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:2000'],
            'is_available' => ['nullable', 'boolean'],
            'variations' => ['nullable', 'array'],
            'variations.*.variation_name' => ['nullable', 'string', 'max:120'],
            'variations.*.pos_price' => ['nullable', 'numeric', 'min:0'],
            'variations.*.web_price' => ['nullable', 'numeric', 'min:0'],
            'variations.*.mobile_price' => ['nullable', 'numeric', 'min:0'],
            'addons' => ['nullable', 'array'],
            'addons.*.addon_item_id' => [
                'nullable',
                Rule::exists('menu_items', 'id')->where(function ($query) use ($restaurantId) {
                    $query->where('restaurant_id', $restaurantId);
                }),
            ],
            'addons.*.addon_name' => ['nullable', 'string', 'max:255'],
            'addons.*.addon_price' => ['nullable', 'numeric', 'min:0'],
            'addons.*.pos_price' => ['nullable', 'numeric', 'min:0'],
            'addons.*.web_price' => ['nullable', 'numeric', 'min:0'],
            'addons.*.mobile_price' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function syncItemRelations(array $validated, int $restaurantId, MenuItem $item): void
    {
        $variationRows = $validated['variations'] ?? [];
        foreach ($variationRows as $variationRow) {
            $name = trim((string) ($variationRow['variation_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $variation = Variation::firstOrCreate(
                [
                    'created_by' => $restaurantId,
                    'variation_name' => $name,
                ],
                [
                    'created_by_super_admin' => 0,
                    'department_id' => null,
                ]
            );

            ItemVariation::updateOrCreate(
                [
                    'item_id' => $item->id,
                    'variation_id' => $variation->id,
                ],
                [
                    'user_id' => $restaurantId,
                    'pos_price' => $variationRow['pos_price'] ?? 0,
                    'web_price' => $variationRow['web_price'] ?? 0,
                    'mobile_price' => $variationRow['mobile_price'] ?? 0,
                ]
            );
        }

        $addonRows = $validated['addons'] ?? [];
        foreach ($addonRows as $addonRow) {
            $addonItemId = $addonRow['addon_item_id'] ?? null;

            if (empty($addonItemId)) {
                $addonName = trim((string) ($addonRow['addon_name'] ?? ''));
                if ($addonName === '') {
                    continue;
                }

                $addonItem = MenuItem::firstOrCreate(
                    [
                        'restaurant_id' => $restaurantId,
                        'name' => $addonName,
                    ],
                    [
                        'category_id' => (int) $validated['category_id'],
                        'description' => null,
                        'price' => $addonRow['addon_price'] ?? 0,
                        'is_available' => 1,
                        'image' => null,
                    ]
                );

                $addonItemId = $addonItem->id;
            }

            if ((int) $addonItemId === (int) $item->id) {
                continue;
            }

            ItemAddon::updateOrCreate(
                [
                    'item_id' => $item->id,
                    'addon_item_id' => (int) $addonItemId,
                ],
                [
                    'user_id' => $restaurantId,
                    'pos_price' => $addonRow['pos_price'] ?? 0,
                    'web_price' => $addonRow['web_price'] ?? 0,
                    'mobile_price' => $addonRow['mobile_price'] ?? 0,
                ]
            );
        }
    }

}
