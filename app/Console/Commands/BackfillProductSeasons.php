<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BackfillProductSeasons extends Command
{
    protected $signature = 'bloomgift:backfill-product-seasons
        {--mapping=database/data/product-season-mapping.json : File JSON ánh xạ mùa vụ}
        {--dry-run : Chỉ kiểm tra, không ghi dữ liệu}';

    protected $description = 'Phân mùa sản phẩm theo mapping riêng, chỉ sửa trường season';

    public function handle(): int
    {
        $path = base_path((string) $this->option('mapping'));
        if (! is_file($path)) {
            $this->error('Không tìm thấy mapping: '.$path);

            return self::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows)) {
            $this->error('Mapping mùa vụ phải là JSON array hợp lệ.');

            return self::FAILURE;
        }

        $changes = collect();
        foreach ($rows as $row) {
            $validator = Validator::make($row, [
                'slug' => ['required', 'string'],
                'season' => ['required', Rule::in(Product::SEASONS)],
                'reason' => ['required', 'string', 'max:500'],
            ]);
            if ($validator->fails()) {
                $this->error('Mapping không hợp lệ: '.$validator->errors()->first());

                return self::FAILURE;
            }

            $product = Product::where('slug', $row['slug'])->first();
            if (! $product) {
                $this->warn('Không tìm thấy sản phẩm: '.$row['slug']);

                continue;
            }
            if ($product->season !== $row['season']) {
                $changes->push([$product, $row]);
            }
        }

        $this->info('Sản phẩm cần đổi mùa: '.$changes->count().'.');
        if ($this->option('dry-run')) {
            $this->comment('Dry-run: không có dữ liệu nào được thay đổi.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes): void {
            foreach ($changes as [$product, $row]) {
                DB::table('products')->where('id', $product->id)->update(['season' => $row['season']]);
            }
        });
        $this->info('Đã cập nhật mùa vụ cho '.$changes->count().' sản phẩm.');

        return self::SUCCESS;
    }
}
