<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductCostHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BackfillProductCosts extends Command
{
    protected $signature = 'bloomgift:backfill-product-costs
        {--mapping=database/data/product-cost-backfill.json : File JSON chứa giá vốn đã xác nhận}
        {--dry-run : Chỉ kiểm tra, không ghi dữ liệu}';

    protected $description = 'Bổ sung giá vốn thật còn thiếu theo mapping riêng, không sửa dữ liệu sản phẩm khác';

    public function handle(): int
    {
        $path = base_path((string) $this->option('mapping'));
        if (! is_file($path)) {
            $this->error('Không tìm thấy mapping: '.$path);

            return self::FAILURE;
        }
        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows)) {
            $this->error('Mapping phải là JSON array hợp lệ.');

            return self::FAILURE;
        }

        $mapped = collect($rows)->keyBy('slug');
        $missing = Product::whereNull('cost_price')->orWhere('cost_price', '<=', 0)->orderBy('id')->get();
        $ready = collect();
        foreach ($missing as $product) {
            $row = $mapped->get($product->slug);
            if (! $row) {
                $this->warn("Thiếu mapping: {$product->id} · {$product->name} ({$product->slug})");

                continue;
            }
            $validator = Validator::make($row, [
                'slug' => ['required', 'string'],
                'cost_price' => ['required', 'numeric', 'gt:0'],
                'sale_price' => ['nullable', 'numeric', 'gt:cost_price'],
                'confirmed' => ['accepted'],
                'reason' => ['nullable', 'string', 'max:1000'],
            ]);
            if ($validator->fails()) {
                $this->error("Mapping không hợp lệ cho {$product->slug}: ".$validator->errors()->first());

                continue;
            }
            if ((float) $row['cost_price'] >= (float) $product->price) {
                $this->error("Mapping không hợp lệ cho {$product->slug}: giá vốn phải nhỏ hơn giá bán.");

                continue;
            }
            if (isset($row['sale_price']) && (float) $row['sale_price'] >= (float) $product->price) {
                $this->error("Mapping không hợp lệ cho {$product->slug}: giá sale phải nhỏ hơn giá bán.");

                continue;
            }
            $ready->push([$product, $validator->validated()]);
        }

        $this->info("Product thiếu giá vốn: {$missing->count()}; mapping hợp lệ: {$ready->count()}.");
        if ($this->option('dry-run')) {
            $this->comment('Dry-run: không có dữ liệu nào được thay đổi.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($ready): void {
            foreach ($ready as [$product, $row]) {
                $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                if ($locked->cost_price !== null && (float) $locked->cost_price > 0) {
                    continue;
                }
                $updates = ['cost_price' => $row['cost_price']];
                if ($locked->sale_price === null && isset($row['sale_price'])) {
                    $updates['sale_price'] = $row['sale_price'];
                }
                $locked->update($updates);
                ProductCostHistory::create([
                    'product_id' => $locked->id,
                    'old_cost_price' => null,
                    'new_cost_price' => $row['cost_price'],
                    'effective_at' => now(),
                    'changed_by' => null,
                    'reason' => $row['reason'] ?? 'Backfill từ mapping giá vốn đã xác nhận',
                ]);
            }
        });
        $this->info("Đã bổ sung giá vốn cho {$ready->count()} sản phẩm.");

        return self::SUCCESS;
    }
}
