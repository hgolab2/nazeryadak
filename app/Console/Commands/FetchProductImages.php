<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\IsacoImageService;
use Illuminate\Console\Command;

/**
 * پر کردن تصویر و توضیحات محصولات از isaco.ir به‌صورت آفلاین.
 *
 * این کار قبلاً هنگام رندر صفحه انجام می‌شد و هر بازدید از فروشگاه
 * ده‌ها درخواست به سایت ایساکو می‌فرستاد. حالا فقط از خط فرمان اجرا می‌شود.
 */
class FetchProductImages extends Command
{
    protected $signature = 'products:fetch-images
                            {--limit=100 : چند محصول در این اجرا پردازش شود}
                            {--sleep=1 : فاصله‌ی بین محصولات به ثانیه}
                            {--all : همه‌ی محصولات بدون تصویر}';

    protected $description = 'دریافت تصویر و توضیحات محصولات از isaco.ir (آفلاین، خارج از چرخه‌ی درخواست وب)';

    public function handle(): int
    {
        $service = (new IsacoImageService())->allowRemote();

        $query = Product::where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('file_path')
                  ->orWhere('file_path', '')
                  ->orWhere('file_path', '/images/no-image.svg');
            });

        $total = (clone $query)->count();
        $limit = $this->option('all') ? $total : (int) $this->option('limit');
        $sleep = (float) $this->option('sleep');

        $this->info("محصولات بدون تصویر: {$total} — در این اجرا: {$limit}");

        $products = $query->limit($limit)->get();
        if ($products->isEmpty()) {
            $this->info('چیزی برای پردازش نیست.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $withImage = 0;
        $withDesc = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                $result = $service->fetchForProduct($product);
                if (!empty($result['image'])) {
                    $withImage++;
                }
                if (!empty($result['description'])) {
                    $withDesc++;
                }
                if (empty($result['image'])) {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->warn("محصول #{$product->id}: " . $e->getMessage());
            }

            $bar->advance();
            if ($sleep > 0) {
                usleep((int) ($sleep * 1_000_000));
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("تصویر گرفته شد: {$withImage}");
        $this->info("توضیحات گرفته شد: {$withDesc}");
        $this->info("بدون نتیجه: {$failed}");

        return self::SUCCESS;
    }
}
