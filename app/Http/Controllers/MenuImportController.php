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
use Illuminate\Validation\Rule;



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
                $sourceItemMap = [];
                $relationQueue = [];

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
                            $imageUrl = $this->resolveJsonItemImage((array) $itemData);

                            $item = \App\Models\MenuItem::create([
                                'restaurant_id' => $restaurant->id,
                                'category_id' => $category->id,
                                'name' => $itemData['name'],
                                'description' => $itemData['description'] ?? null,
                                'price' => $itemData['web_price'] ?? 0,
                                'is_available' => $itemData['status'],
                                'image' => $imageUrl,
                            ]);

                            if (is_numeric($itemData['item_id'] ?? null)) {
                                $sourceItemMap[(int) $itemData['item_id']] = (int) $item->id;
                            }

                            $relationQueue[] = [
                                'item' => $item,
                                'category_id' => (int) $category->id,
                                'item_data' => (array) $itemData,
                            ];
                        }
                    }
                }

                foreach ($relationQueue as $queued) {
                    $this->syncJsonItemRelations(
                        $restaurant->id,
                        (int) $queued['category_id'],
                        $queued['item'],
                        $queued['item_data'],
                        $sourceItemMap
                    );
                }
            });

            $this->triggerReindex($restaurant->id);

            return redirect()
                ->route('restaurant.dashboard')
                ->with('success', 'JSON menu imported successfully');
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

    private function resolveJsonItemImage(array $itemData): ?string
    {
        $direct = $itemData['image'] ?? $itemData['image_url'] ?? null;
        if (is_string($direct) && $direct !== '') {
            return $direct;
        }

        $attachment = $itemData['item_attachment'] ?? $itemData['attachment'] ?? null;
        if (!is_array($attachment)) {
            return null;
        }

        $base = trim((string) ($attachment['base_url'] ?? ''));
        $path = trim((string) ($attachment['attachment_url'] ?? ''));
        if ($base === '' && $path === '') {
            return null;
        }
        if ($base === '') {
            return $path;
        }
        if ($path === '') {
            return $base;
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    private function syncJsonItemRelations(
        int $restaurantId,
        int $categoryId,
        MenuItem $item,
        array $itemData,
        array $sourceItemMap
    ): void {
        $variationRows = $itemData['variations']
            ?? $itemData['item_variation']
            ?? $itemData['customize_item_variation']
            ?? $itemData['variation_data']
            ?? [];

        if (is_array($variationRows)) {
            foreach ($variationRows as $variationRow) {
                if (!is_array($variationRow)) {
                    continue;
                }

                $detail = $variationRow['variation_detail'][0] ?? [];
                $name = trim((string) (
                    $variationRow['variation_name']
                    ?? (is_array($detail) ? ($detail['variation_name'] ?? null) : null)
                    ?? ''
                ));
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

                $base = (float) ($variationRow['variation_price'] ?? 0);
                ItemVariation::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'variation_id' => $variation->id,
                    ],
                    [
                        'user_id' => $restaurantId,
                        'pos_price' => (float) ($variationRow['pos_price'] ?? $base),
                        'web_price' => (float) ($variationRow['web_price'] ?? $base),
                        'mobile_price' => (float) ($variationRow['mobile_price'] ?? $base),
                    ]
                );
            }
        }

        $addonRows = $itemData['addons']
            ?? $itemData['addon_data']
            ?? $itemData['add_ons']
            ?? $itemData['addon_items']
            ?? [];

        if (is_array($addonRows)) {
            foreach ($addonRows as $addonRow) {
                if (!is_array($addonRow)) {
                    continue;
                }

                $addonItemData = is_array($addonRow['addon_item'] ?? null) ? $addonRow['addon_item'] : [];
                $sourceAddonItemId = $addonRow['addon_item_id'] ?? ($addonItemData['item_id'] ?? null);
                $addonItemId = null;

                if (is_numeric($sourceAddonItemId)) {
                    $addonItemId = $sourceItemMap[(int) $sourceAddonItemId] ?? null;
                }

                if (!$addonItemId) {
                    $addonName = trim((string) (
                        $addonRow['addon_name']
                        ?? $addonRow['name']
                        ?? ($addonItemData['name'] ?? '')
                    ));

                    if ($addonName === '') {
                        continue;
                    }

                    $addonPrice = (float) (
                        $addonRow['addon_price']
                        ?? $addonRow['offered_price']
                        ?? ($addonItemData['price'] ?? 0)
                    );

                    $addonItem = MenuItem::firstOrCreate(
                        [
                            'restaurant_id' => $restaurantId,
                            'name' => $addonName,
                        ],
                        [
                            'category_id' => $categoryId,
                            'description' => null,
                            'price' => $addonPrice,
                            'is_available' => 1,
                            'image' => $this->resolveJsonItemImage($addonItemData),
                        ]
                    );

                    $addonItemId = (int) $addonItem->id;
                }

                if ((int) $addonItemId === (int) $item->id) {
                    continue;
                }

                $base = (float) ($addonRow['addon_price'] ?? $addonRow['offered_price'] ?? 0);
                ItemAddon::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'addon_item_id' => (int) $addonItemId,
                    ],
                    [
                        'user_id' => $restaurantId,
                        'pos_price' => (float) ($addonRow['pos_price'] ?? $base),
                        'web_price' => (float) ($addonRow['web_price'] ?? $addonRow['web_offered_price'] ?? $base),
                        'mobile_price' => (float) ($addonRow['mobile_price'] ?? $addonRow['mob_offered_price'] ?? $base),
                    ]
                );
            }
        }
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
