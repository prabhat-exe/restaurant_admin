<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\MenuItem;
use App\Models\Variation;
use App\Models\ItemVariation;
use App\Models\ItemAddon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MenuExcelImport implements ToCollection, WithHeadingRow
{
    protected $restaurant;

    public function __construct($restaurant)
    {
        $this->restaurant = $restaurant;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            // -----------------------------
            // CATEGORY
            // -----------------------------
            $category = Category::firstOrCreate(
                [
                    'restaurant_id' => $this->restaurant->id,
                    'name' => trim($row['category_name'])
                ]
            );

            // -----------------------------
            // SUB CATEGORY
            // -----------------------------
            $subCategory = SubCategory::firstOrCreate(
                [
                    'restaurant_id' => $this->restaurant->id,
                    'category_id' => $category->id,
                    'name' => trim($row['sub_category_name'])
                ]
            );

            // -----------------------------
            // ITEM
            // -----------------------------
            $item = MenuItem::firstOrCreate(
                [
                    'restaurant_id' => $this->restaurant->id,
                    'name' => trim($row['item_name'])
                ],
                [
                    'category_id' => $category->id,
                    'description' => $row['description'] ?? null,
                    'price' => $row['base_price'] ?? 0,
                    'is_available' => 1,
                ]
            );

            // -----------------------------
            // VARIATION (Dynamic)
            // -----------------------------
            if (!empty($row['variation_name'])) {

                $variationName = trim($row['variation_name']);

                // Create variation if not exists
                $variation = Variation::firstOrCreate(
                    [
                        'variation_name' => $variationName,
                        'created_by' => $this->restaurant->id
                    ],
                    [
                        'created_by_super_admin' => 0,
                        'department_id' => null
                    ]
                );

                // Attach variation to item (no duplicate)
                ItemVariation::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'variation_id' => $variation->id,
                    ],
                    [
                        'user_id' => $this->restaurant->id,
                        'pos_price' => $row['pos_price'] ?? 0,
                        'web_price' => $row['web_price'] ?? 0,
                        'mobile_price' => $row['mobile_price'] ?? 0,
                    ]
                );
            }

            // -----------------------------
            // ADDONS (supports single or pipe-separated values)
            // -----------------------------
            $addonNames = $this->parseList($row['addon_item_name'] ?? $row['addon_name'] ?? null);
            if (!empty($addonNames)) {
                $addonPosPrices = $this->parseList($row['addon_pos_price'] ?? null);
                $addonWebPrices = $this->parseList($row['addon_web_price'] ?? null);
                $addonMobilePrices = $this->parseList($row['addon_mobile_price'] ?? null);
                $addonBasePrices = $this->parseList($row['addon_base_price'] ?? $row['addon_price'] ?? null);
                $addonDescriptions = $this->parseList($row['addon_description'] ?? null);

                $addonCategoryName = trim((string) ($row['addon_category_name'] ?? $row['category_name'] ?? 'Addon'));
                $addonSubCategoryName = trim((string) ($row['addon_sub_category_name'] ?? $row['sub_category_name'] ?? $addonCategoryName));

                $addonCategory = Category::firstOrCreate([
                    'restaurant_id' => $this->restaurant->id,
                    'name' => $addonCategoryName,
                ]);

                $addonSubCategory = SubCategory::firstOrCreate([
                    'restaurant_id' => $this->restaurant->id,
                    'category_id' => $addonCategory->id,
                    'name' => $addonSubCategoryName,
                ]);

                foreach ($addonNames as $index => $addonNameRaw) {
                    $addonName = trim((string) $addonNameRaw);
                    if ($addonName === '') {
                        continue;
                    }

                    $addonDescription = $addonDescriptions[$index] ?? null;
                    $addonWebPrice = $this->toMoney($addonWebPrices[$index] ?? null, 0);
                    $addonBasePrice = $this->toMoney($addonBasePrices[$index] ?? null, $addonWebPrice);

                    $addonItem = MenuItem::firstOrCreate(
                        [
                            'restaurant_id' => $this->restaurant->id,
                            'name' => $addonName,
                        ],
                        [
                            'category_id' => $addonCategory->id,
                            'description' => $addonDescription,
                            'price' => $addonBasePrice,
                            'is_available' => 1,
                        ]
                    );

                    ItemAddon::updateOrCreate(
                        [
                            'item_id' => $item->id,
                            'addon_item_id' => $addonItem->id,
                        ],
                        [
                            'user_id' => $this->restaurant->id,
                            'pos_price' => $this->toMoney($addonPosPrices[$index] ?? null, $addonBasePrice),
                            'web_price' => $addonWebPrice > 0 ? $addonWebPrice : $addonBasePrice,
                            'mobile_price' => $this->toMoney($addonMobilePrices[$index] ?? null, $addonBasePrice),
                        ]
                    );
                }
            }
        }
    }

    private function parseList($value): array
    {
        if ($value === null) {
            return [];
        }

        $string = trim((string) $value);
        if ($string === '') {
            return [];
        }

        $parts = array_map('trim', explode('|', $string));
        return array_values(array_filter($parts, fn ($v) => $v !== ''));
    }

    private function toMoney($value, float $fallback = 0): float
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        $sanitized = str_replace(',', '', trim((string) $value));
        if (!is_numeric($sanitized)) {
            return $fallback;
        }

        return (float) $sanitized;
    }
}
