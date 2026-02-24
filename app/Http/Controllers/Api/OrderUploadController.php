<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderUploadController extends Controller
{
    /**
     * Upload restaurant/store JSON and store/update menu/category/item/addon/variation/tax data
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'json_file' => 'required|file|mimes:json',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $json = json_decode(file_get_contents($request->file('json_file')->getRealPath()), true);
        if (!$json) {
            return response()->json(['success' => false, 'errors' => ['Invalid JSON']], 422);
        }

        // Example: Store/update menu/category/item/addon/variation/tax data
        // You must adapt this to your JSON structure
        DB::beginTransaction();
        try {
            // Store/Update Store
            $store = \App\Models\Store::updateOrCreate(
                ['store_id' => $json['store_id']],
                ['store_name' => $json['store_name'], 'other_fields' => $json['other_fields'] ?? null]
            );

            // Store/Update Categories
            foreach ($json['categories'] ?? [] as $category) {
                \App\Models\Category::updateOrCreate(
                    ['category_id' => $category['category_id']],
                    ['store_id' => $store->store_id, 'name' => $category['name'], 'other_fields' => $category['other_fields'] ?? null]
                );
            }

            // Store/Update Items
            foreach ($json['items'] ?? [] as $item) {
                \App\Models\Item::updateOrCreate(
                    ['item_id' => $item['item_id']],
                    ['store_id' => $store->store_id, 'name' => $item['name'], 'price' => $item['price'], 'other_fields' => $item['other_fields'] ?? null]
                );
            }

            // Store/Update Addons
            foreach ($json['addons'] ?? [] as $addon) {
                \App\Models\Addon::updateOrCreate(
                    ['addon_id' => $addon['addon_id']],
                    ['store_id' => $store->store_id, 'name' => $addon['name'], 'other_fields' => $addon['other_fields'] ?? null]
                );
            }

            // Store/Update Variations
            foreach ($json['variations'] ?? [] as $variation) {
                \App\Models\Variation::updateOrCreate(
                    ['variation_id' => $variation['variation_id']],
                    ['store_id' => $store->store_id, 'name' => $variation['name'], 'price' => $variation['price'], 'other_fields' => $variation['other_fields'] ?? null]
                );
            }

            // Store/Update Taxes
            foreach ($json['taxes'] ?? [] as $tax) {
                \App\Models\Tax::updateOrCreate(
                    ['tax_id' => $tax['tax_id']],
                    ['store_id' => $store->store_id, 'name' => $tax['name'], 'rate' => $tax['rate'], 'other_fields' => $tax['other_fields'] ?? null]
                );
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data uploaded/updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'errors' => [$e->getMessage()]], 500);
        }
    }
}
