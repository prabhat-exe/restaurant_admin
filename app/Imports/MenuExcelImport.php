<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\MenuItem;
use App\Models\Variation;
use App\Models\ItemVariation;
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
        }
    }
}