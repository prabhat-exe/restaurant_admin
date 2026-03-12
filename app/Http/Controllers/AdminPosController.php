<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ItemAddon;
use App\Models\ItemVariation;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\SubCategory;
use App\Models\TaxClass;
use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class AdminPosController extends Controller
{
    public function index()
    {
        $posSystems = Restaurant::query()
            ->where('is_pos', true)
            ->latest()
            ->paginate(20);

        return view('admin.pos_index', [
            'posSystems' => $posSystems,
        ]);
    }

    public function create()
    {
        return view('admin.pos_create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePosPayload($request, null);

        Restaurant::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'],
            'is_pos' => true,
            'menu_url' => $validated['menu_url'],
            'client_id' => (string) $validated['client_id'],
            'public_key' => $validated['public_key'],
            'secret_key' => $validated['secret_key'],
            'last_synced_at' => null,
            'last_sync_status' => null,
            'last_sync_error' => null,
        ]);

        return redirect()
            ->route('admin.pos.index')
            ->with('success', 'POS registered successfully.');
    }

    public function update(Request $request, Restaurant $pos)
    {
        $validated = $this->validatePosPayload($request, $pos);

        if (!$pos->is_pos) {
            return redirect()
                ->route('admin.pos.index')
                ->with('error', 'Selected record is not marked as POS.');
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'],
            'menu_url' => $validated['menu_url'],
            'client_id' => (string) $validated['client_id'],
            'public_key' => $validated['public_key'],
            'secret_key' => $validated['secret_key'],
        ];
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $pos->update($updateData);

        return redirect()
            ->route('admin.pos.index')
            ->with('success', 'POS updated successfully.');
    }

    public function syncMenu(Restaurant $pos)
    {
        if (!$pos->is_pos) {
            return redirect()
                ->route('admin.pos.index')
                ->with('error', 'Selected record is not marked as POS.');
        }

        if (!$pos->is_active) {
            return redirect()
                ->route('admin.pos.index')
                ->with('error', 'This POS is inactive. Activate it first to sync menu.');
        }

        try {
            $payload = $this->fetchPosMenuPayload($pos);

            $categoryData = $this->extractCategoryData($payload);
            if (empty($categoryData)) {
                $keys = implode(', ', array_keys($payload));
                throw new \RuntimeException('No menu category data found in POS response. Top-level keys: ' . $keys);
            }

            $restaurantId = (int) $pos->id;

            DB::transaction(function () use ($restaurantId, $categoryData) {
                $this->clearRestaurantMenu($restaurantId);
                $this->saveMenuPayload($restaurantId, $categoryData);
            });

            $itemCount = MenuItem::query()->where('restaurant_id', $restaurantId)->count();
            $variationCount = ItemVariation::query()->where('user_id', $restaurantId)->count();
            $addonCount = ItemAddon::query()->where('user_id', $restaurantId)->count();
            $imageCount = MenuItem::query()
                ->where('restaurant_id', $restaurantId)
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->count();

            $pos->update([
                'last_synced_at' => now(),
                'last_sync_status' => 'success',
                'last_sync_error' => null,
            ]);
        } catch (\Throwable $e) {
            $pos->update([
                'last_synced_at' => now(),
                'last_sync_status' => 'failed',
                'last_sync_error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.pos.index')
                ->with('error', 'Menu sync failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.pos.index')
            ->with('success', "POS menu synced. items={$itemCount}, variations={$variationCount}, addons={$addonCount}, images={$imageCount}");
    }

    private function fetchPosMenuPayload(Restaurant $pos): array
    {
        $headers = [
            'X-Client-Id' => $pos->client_id,
            'X-Public-Key' => $pos->public_key,
            'X-Secret-Key' => $pos->secret_key,
        ];

        $client = Http::timeout(30)->acceptJson()->withHeaders($headers);

        $response = $client->get($pos->menu_url);
        if ($response->status() === 405) {
            $response = $client->post($pos->menu_url);
        }

        if ($response->failed()) {
            throw new \RuntimeException('POS endpoint returned status ' . $response->status());
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            $decoded = json_decode((string) $response->body(), true);
            $payload = is_array($decoded) ? $decoded : null;
        }

        if (!is_array($payload)) {
            throw new \RuntimeException('POS response is not a valid JSON object.');
        }

        return $payload;
    }

    private function validatePosPayload(Request $request, ?Restaurant $pos): array
    {
        $passwordRules = ['nullable', 'string', 'min:6', 'max:255'];
        if ($pos === null) {
            $passwordRules = ['required', 'string', 'min:6', 'max:255'];
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                $pos
                    ? Rule::unique('restaurants', 'email')->ignore($pos->id)
                    : Rule::unique('restaurants', 'email'),
            ],
            'password' => $passwordRules,
            'menu_url' => ['required', 'url', 'max:2000'],
            'client_id' => ['required', 'numeric'],
            'public_key' => ['required', 'string', 'max:255'],
            'secret_key' => ['required', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];

        $validated = $request->validate($rules);
        if ($pos !== null && empty($validated['password'])) {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function extractCategoryData(array $payload): array
    {
        $candidates = [
            $payload['Category_data'] ?? null,
            $payload['category_data'] ?? null,
            $payload['categories'] ?? null,
            $payload['menu'] ?? null,
            $payload['data']['Category_data'] ?? null,
            $payload['data']['category_data'] ?? null,
            $payload['data']['categories'] ?? null,
            $payload['data']['menu'] ?? null,
            $payload['result']['categories'] ?? null,
            $payload['result']['menu'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && !empty($candidate)) {
                return $this->normalizeCategoryRows($candidate);
            }
        }

        if (isset($payload['items']) && is_array($payload['items']) && !empty($payload['items'])) {
            return [[
                'name' => 'Menu',
                'sub_category_data' => [[
                    'name' => 'All Items',
                    'item_data' => $this->normalizeItems($payload['items']),
                    'status' => 1,
                ]],
                'status' => 1,
            ]];
        }

        if (isset($payload['data']['items']) && is_array($payload['data']['items']) && !empty($payload['data']['items'])) {
            return [[
                'name' => 'Menu',
                'sub_category_data' => [[
                    'name' => 'All Items',
                    'item_data' => $this->normalizeItems($payload['data']['items']),
                    'status' => 1,
                ]],
                'status' => 1,
            ]];
        }

        return [];
    }

    private function normalizeCategoryRows(array $rows): array
    {
        $rows = $this->asList($rows);
        $normalized = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? $row['category_name'] ?? $row['title'] ?? 'Category'));

            $rawSubCategories = $row['sub_category_data']
                ?? $row['subcategory_data']
                ?? $row['sub_categories']
                ?? $row['subcategories']
                ?? null;

            if (!empty($rawSubCategories) && is_array($rawSubCategories)) {
                $rawSubCategories = $this->asList($rawSubCategories);
                $subCategoryRows = [];
                foreach ($rawSubCategories as $subRow) {
                    if (!is_array($subRow)) {
                        continue;
                    }

                    $subItems = [];
                    if (isset($subRow['item_data']) && is_array($subRow['item_data'])) {
                        $subItems = $subRow['item_data'];
                    } elseif (isset($subRow['items']) && is_array($subRow['items'])) {
                        $subItems = $subRow['items'];
                    } elseif (isset($subRow['products']) && is_array($subRow['products'])) {
                        $subItems = $subRow['products'];
                    } elseif (isset($subRow['menu_items']) && is_array($subRow['menu_items'])) {
                        $subItems = $subRow['menu_items'];
                    } elseif (isset($subRow['itemData']) && is_array($subRow['itemData'])) {
                        $subItems = $subRow['itemData'];
                    }

                    $subCategoryRows[] = [
                        'name' => trim((string) ($subRow['name'] ?? $subRow['sub_category_name'] ?? $name)),
                        'description' => $subRow['description'] ?? null,
                        'status' => $subRow['status'] ?? 1,
                        'item_data' => $this->normalizeItems($subItems),
                    ];
                }

                $normalized[] = [
                    'name' => $name,
                    'description' => $row['description'] ?? null,
                    'status' => $row['status'] ?? 1,
                    'sub_category_data' => $subCategoryRows,
                ];
                continue;
            }

            if (isset($row['item_data']) && is_array($row['item_data'])) {
                $normalized[] = [
                    'name' => $name,
                    'status' => $row['status'] ?? 1,
                    'sub_category_data' => [[
                        'name' => $name,
                        'status' => 1,
                        'item_data' => $this->normalizeItems($row['item_data']),
                    ]],
                ];
                continue;
            }

            $items = [];
            if (isset($row['items']) && is_array($row['items'])) {
                $items = $row['items'];
            } elseif (isset($row['products']) && is_array($row['products'])) {
                $items = $row['products'];
            } elseif (isset($row['menu_items']) && is_array($row['menu_items'])) {
                $items = $row['menu_items'];
            } elseif (isset($row['itemData']) && is_array($row['itemData'])) {
                $items = $row['itemData'];
            }

            if (!empty($items)) {
                $normalized[] = [
                    'name' => $name,
                    'status' => $row['status'] ?? 1,
                    'sub_category_data' => [[
                        'name' => $name,
                        'status' => 1,
                        'item_data' => $this->normalizeItems($items),
                    ]],
                ];
            }
        }

        return $normalized;
    }

    private function normalizeItems(array $items): array
    {
        $items = $this->asList($items);
        $normalized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized[] = [
                'source_item_id' => $item['item_id'] ?? $item['id'] ?? null,
                'name' => trim((string) ($item['name'] ?? $item['item_name'] ?? $item['title'] ?? 'Item')),
                'description' => $item['description'] ?? $item['desc'] ?? null,
                'price' => $item['price'] ?? $item['web_price'] ?? $item['selling_price'] ?? $item['amount'] ?? 0,
                'status' => $item['status'] ?? $item['is_available'] ?? 1,
                'image' => $this->resolveImageUrl($item),
                'variations' => $this->normalizeVariations($item),
                'addons' => $this->normalizeAddons($item),
            ];
        }

        return $normalized;
    }

    private function clearRestaurantMenu(int $restaurantId): void
    {
        ItemAddon::where('user_id', $restaurantId)->delete();
        ItemVariation::where('user_id', $restaurantId)->delete();
        MenuItem::where('restaurant_id', $restaurantId)->delete();
        SubCategory::where('restaurant_id', $restaurantId)->delete();
        Category::where('restaurant_id', $restaurantId)->delete();
        TaxClass::where('restaurant_id', $restaurantId)->delete();
        Variation::where('created_by', $restaurantId)->delete();
    }

    private function saveMenuPayload(int $restaurantId, array $categoryData): void
    {
        $itemsBySourceId = [];
        $relationsQueue = [];

        foreach ($categoryData as $categoryRow) {
            $category = Category::create([
                'restaurant_id' => $restaurantId,
                'name' => trim((string) ($categoryRow['name'] ?? 'Category')),
                'description' => $categoryRow['description'] ?? null,
                'status' => (bool) ($categoryRow['status'] ?? 1),
            ]);

            $subCategoryRows = $categoryRow['sub_category_data'] ?? [];
            if (!is_array($subCategoryRows) || empty($subCategoryRows)) {
                $subCategoryRows = [[
                    'name' => $category->name,
                    'status' => 1,
                    'item_data' => $categoryRow['item_data'] ?? [],
                ]];
            }

            foreach ($subCategoryRows as $subCategoryRow) {
                $subCategory = SubCategory::create([
                    'restaurant_id' => $restaurantId,
                    'category_id' => $category->id,
                    'name' => trim((string) ($subCategoryRow['name'] ?? $category->name)),
                    'description' => $subCategoryRow['description'] ?? null,
                    'status' => (bool) ($subCategoryRow['status'] ?? 1),
                ]);

                $itemRows = $subCategoryRow['item_data'] ?? [];
                if (!is_array($itemRows)) {
                    $itemRows = [];
                }

                foreach ($itemRows as $itemRow) {
                    $item = MenuItem::create([
                        'restaurant_id' => $restaurantId,
                        'category_id' => $subCategory->category_id,
                        'name' => trim((string) ($itemRow['name'] ?? 'Item')),
                        'description' => $itemRow['description'] ?? null,
                        'price' => (float) ($itemRow['price'] ?? $itemRow['web_price'] ?? 0),
                        'is_available' => (bool) ($itemRow['status'] ?? 1),
                        'image' => $itemRow['image'] ?? null,
                    ]);

                    $sourceItemId = $itemRow['source_item_id'] ?? null;
                    if (is_numeric($sourceItemId)) {
                        $itemsBySourceId[(int) $sourceItemId] = (int) $item->id;
                    }

                    $relationsQueue[] = [
                        'item' => $item,
                        'category_id' => $subCategory->category_id,
                        'variations' => is_array($itemRow['variations'] ?? null) ? $itemRow['variations'] : [],
                        'addons' => is_array($itemRow['addons'] ?? null) ? $itemRow['addons'] : [],
                    ];
                }
            }
        }

        foreach ($relationsQueue as $entry) {
            $this->syncPosItemRelations(
                $restaurantId,
                $entry['item'],
                (int) $entry['category_id'],
                $entry['variations'],
                $entry['addons'],
                $itemsBySourceId
            );
        }
    }

    private function syncPosItemRelations(
        int $restaurantId,
        MenuItem $item,
        int $defaultCategoryId,
        array $variationRows,
        array $addonRows,
        array $itemsBySourceId
    ): void {
        foreach ($variationRows as $variationRow) {
            if (!is_array($variationRow)) {
                continue;
            }

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

            $basePrice = (float) ($variationRow['variation_price'] ?? 0);
            $webPrice = (float) ($variationRow['web_price'] ?? $basePrice);
            $mobilePrice = (float) ($variationRow['mobile_price'] ?? $webPrice);
            $posPrice = (float) ($variationRow['pos_price'] ?? $basePrice);

            ItemVariation::updateOrCreate(
                [
                    'item_id' => $item->id,
                    'variation_id' => $variation->id,
                ],
                [
                    'user_id' => $restaurantId,
                    'pos_price' => $posPrice,
                    'web_price' => $webPrice,
                    'mobile_price' => $mobilePrice,
                ]
            );
        }

        foreach ($addonRows as $addonRow) {
            if (!is_array($addonRow)) {
                continue;
            }

            $addonItemId = null;
            $sourceAddonItemId = $addonRow['source_addon_item_id'] ?? null;

            if (is_numeric($sourceAddonItemId)) {
                $addonItemId = $itemsBySourceId[(int) $sourceAddonItemId] ?? null;
            }

            if (!$addonItemId && is_numeric($addonRow['addon_item_id'] ?? null)) {
                $candidateId = (int) $addonRow['addon_item_id'];
                if ($candidateId > 0 && MenuItem::query()->where('restaurant_id', $restaurantId)->where('id', $candidateId)->exists()) {
                    $addonItemId = $candidateId;
                }
            }

            if (!$addonItemId) {
                $addonName = trim((string) ($addonRow['addon_name'] ?? ''));
                if ($addonName === '') {
                    continue;
                }

                $addonPrice = (float) ($addonRow['addon_price'] ?? 0);
                $addonItem = MenuItem::firstOrCreate(
                    [
                        'restaurant_id' => $restaurantId,
                        'name' => $addonName,
                    ],
                    [
                        'category_id' => $defaultCategoryId,
                        'description' => null,
                        'price' => $addonPrice,
                        'is_available' => 1,
                        'image' => $addonRow['addon_image'] ?? null,
                    ]
                );

                $addonItemId = (int) $addonItem->id;
            }

            if ((int) $addonItemId === (int) $item->id) {
                continue;
            }

            $addonBasePrice = (float) ($addonRow['addon_price'] ?? 0);
            $addonWebPrice = (float) ($addonRow['web_price'] ?? $addonBasePrice);
            $addonMobilePrice = (float) ($addonRow['mobile_price'] ?? $addonWebPrice);
            $addonPosPrice = (float) ($addonRow['pos_price'] ?? $addonBasePrice);

            ItemAddon::updateOrCreate(
                [
                    'item_id' => $item->id,
                    'addon_item_id' => (int) $addonItemId,
                ],
                [
                    'user_id' => $restaurantId,
                    'pos_price' => $addonPosPrice,
                    'web_price' => $addonWebPrice,
                    'mobile_price' => $addonMobilePrice,
                ]
            );
        }
    }

    private function resolveImageUrl(array $item): ?string
    {
        $directImage = $item['image'] ?? $item['image_url'] ?? null;
        if (!empty($directImage) && is_string($directImage)) {
            return $directImage;
        }
        if (is_array($directImage)) {
            return $this->buildAttachmentUrl($directImage);
        }

        if (!empty($item['item_attachment']) && is_array($item['item_attachment'])) {
            return $this->buildAttachmentUrl($item['item_attachment']);
        }
        if (!empty($item['attachment']) && is_array($item['attachment'])) {
            return $this->buildAttachmentUrl($item['attachment']);
        }
        if (!empty($item['image_data']) && is_array($item['image_data'])) {
            return $this->buildAttachmentUrl($item['image_data']);
        }

        return null;
    }

    private function buildAttachmentUrl(array $attachment): ?string
    {
        $base = isset($attachment['base_url']) ? trim((string) $attachment['base_url']) : '';
        $path = isset($attachment['attachment_url']) ? trim((string) $attachment['attachment_url']) : '';

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

    private function normalizeVariations(array $item): array
    {
        $rawVariations = $item['variations']
            ?? $item['item_variation']
            ?? $item['customize_item_variation']
            ?? $item['variation_data']
            ?? [];
        if (!is_array($rawVariations)) {
            return [];
        }
        $rawVariations = $this->asList($rawVariations);

        $normalized = [];
        foreach ($rawVariations as $variationRow) {
            if (!is_array($variationRow)) {
                continue;
            }

            $detail = $variationRow['variation_detail'][0] ?? [];
            if (!is_array($detail)) {
                $detail = [];
            }

            $variationName = trim((string) (
                $variationRow['variation_name']
                ?? $detail['variation_name']
                ?? $variationRow['name']
                ?? $variationRow['title']
                ?? ''
            ));

            if ($variationName === '') {
                continue;
            }

            $basePrice = (float) (
                $variationRow['variation_price']
                ?? $variationRow['price']
                ?? $variationRow['web_price']
                ?? 0
            );
            $normalized[] = [
                'variation_name' => $variationName,
                'variation_price' => $basePrice,
                'pos_price' => (float) ($variationRow['pos_price'] ?? $basePrice),
                'web_price' => (float) ($variationRow['web_price'] ?? $basePrice),
                'mobile_price' => (float) ($variationRow['mobile_price'] ?? $basePrice),
            ];
        }

        return $normalized;
    }

    private function normalizeAddons(array $item): array
    {
        $rawAddons = $item['addons']
            ?? $item['addon_data']
            ?? $item['add_ons']
            ?? $item['addon_items']
            ?? [];
        if (!is_array($rawAddons)) {
            return [];
        }
        $rawAddons = $this->asList($rawAddons);

        $normalized = [];
        foreach ($rawAddons as $addonRow) {
            if (!is_array($addonRow)) {
                continue;
            }

            $addonItem = $addonRow['addon_item'] ?? [];
            if (!is_array($addonItem)) {
                $addonItem = [];
            }

            $basePrice = (float) (
                $addonRow['addon_price']
                ?? $addonRow['price']
                ?? $addonRow['offered_price']
                ?? $addonRow['web_offered_price']
                ?? $addonRow['mob_offered_price']
                ?? ($addonItem['price'] ?? 0)
            );

            $normalized[] = [
                'source_addon_item_id' => $addonRow['addon_item_id'] ?? ($addonItem['item_id'] ?? null),
                'addon_item_id' => $addonRow['addon_item_id'] ?? null,
                'addon_name' => trim((string) (
                    $addonRow['addon_name']
                    ?? $addonRow['name']
                    ?? $addonRow['addon_item_name']
                    ?? $addonItem['name']
                    ?? ''
                )),
                'addon_price' => $basePrice,
                'pos_price' => (float) ($addonRow['pos_price'] ?? $addonRow['offered_price'] ?? $basePrice),
                'web_price' => (float) ($addonRow['web_price'] ?? $addonRow['web_offered_price'] ?? $basePrice),
                'mobile_price' => (float) ($addonRow['mobile_price'] ?? $addonRow['mob_offered_price'] ?? $basePrice),
                'addon_image' => $this->resolveImageUrl(!empty($addonItem) ? $addonItem : $addonRow),
            ];
        }

        return $normalized;
    }

    private function asList(array $value): array
    {
        if (array_is_list($value)) {
            return $value;
        }

        $allValuesAreArrays = true;
        foreach ($value as $v) {
            if (!is_array($v)) {
                $allValuesAreArrays = false;
                break;
            }
        }

        if ($allValuesAreArrays) {
            return array_values($value);
        }

        return [$value];
    }
}
