<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class BloomGiftExpandedProductSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = json_decode(file_get_contents(database_path('data/bloomgift-expanded-products.json')), true, 512, JSON_THROW_ON_ERROR);
        $pending = array_values(array_filter($catalog, fn ($row) => ! Product::where('slug', $row['slug'])->exists()));
        // Preflight the entire batch before writing anything. Never invent supplier costs.
        foreach ($pending as $row) {
            Validator::make($row, [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'regex:/^[a-z0-9-]+$/'],
                'description' => ['required', 'string'],
                'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
                'cost_price' => ['required', 'numeric', 'min:0', 'lt:price', 'decimal:0,2'],
                'sale_price' => ['required', 'numeric', 'gt:cost_price', 'lt:price', 'decimal:0,2'],
                'cost_confirmed' => ['accepted'],
                'category' => ['required', 'string', 'max:255'],
                'season' => ['required', 'in:spring,summer,autumn,winter,all'],
                'stock' => ['required', 'integer', 'min:0'],
                'is_active' => ['required', 'boolean'],
            ], ['cost_price.required' => 'Chưa có giá vốn thực tế: '.$row['name'], 'cost_confirmed.accepted' => 'Giá vốn chưa được xác nhận: '.$row['name']])->validate();
            $source = public_path('images/expanded/'.$row['slug'].'.webp');
            if (! is_file($source) || @getimagesize($source) === false) {
                throw new RuntimeException('Thiếu ảnh hợp lệ: '.$source);
            }
        }
        DB::transaction(function () use ($pending) {
            foreach ($pending as $row) {
                $category = Category::firstOrCreate(['slug' => Str::slug($row['category'])], ['name' => $row['category']]);
                $image = 'products/expanded/'.$row['slug'].'.webp';
                if (! Storage::disk('public')->exists($image)) {
                    if (! Storage::disk('public')->put($image, file_get_contents(public_path('images/expanded/'.$row['slug'].'.webp')))) {
                        throw new RuntimeException('Không lưu được ảnh '.$image);
                    }
                }
                Product::firstOrCreate(['slug' => $row['slug']], [
                    'name' => $row['name'], 'description' => $row['description'],
                    'price' => $row['price'], 'cost_price' => $row['cost_price'], 'sale_price' => $row['sale_price'],
                    'category_id' => $category->id, 'stock' => $row['stock'],
                    'is_active' => $row['is_active'], 'image' => $image, 'season' => $row['season'],
                ]);
            }
        });
        $this->command?->info('Đã kiểm tra/nhập '.count($pending).' sản phẩm mới. Sản phẩm đã tồn tại được giữ nguyên.');
    }
}
