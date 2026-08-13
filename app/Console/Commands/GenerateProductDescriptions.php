<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * پرکردن انبوه توضیحات محصولاتِ بدون متن.
 *
 * صفحه‌ی محصول بدون توضیحات، «محتوای نازک» است و گوگل یا ایندکسش نمی‌کند
 * یا در رتبه‌ی پایین می‌گذارد. این دستور پیش‌نویس می‌سازد تا تیم به‌جای
 * نوشتن از صفر، فقط ویرایش کند.
 *
 *   php artisan products:describe --limit=200
 *   php artisan products:describe --force        (بازنویسی متن‌های موجود)
 */
class GenerateProductDescriptions extends Command
{
    protected $signature = 'products:describe
                            {--limit=0 : حداکثر تعداد محصول (۰ یعنی بدون سقف)}
                            {--min=20 : توضیحات کوتاه‌تر از این تعداد کاراکتر، خالی حساب می‌شود}
                            {--force : محصولاتی که توضیحات دارند هم بازنویسی شوند}
                            {--dry-run : فقط گزارش بده، چیزی ذخیره نکن}';

    protected $description = 'ساخت پیش‌نویس توضیحات برای محصولات بدون متن';

    public function handle(): int
    {
        $min = (int) $this->option('min');
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        $query = Product::query()->with('categories')->orderBy('id');

        if (! $force) {
            $query->where(function ($q) use ($min) {
                $q->whereNull('description')
                  ->orWhereRaw('CHAR_LENGTH(description) < ?', [$min]);
            });
        }

        $total = (clone $query)->count();
        if ($limit > 0) {
            $query->limit($limit);
        }

        if ($total === 0) {
            $this->info('محصولی برای پردازش پیدا نشد.');

            return self::SUCCESS;
        }

        $this->info(($limit > 0 ? min($limit, $total) : $total) . ' محصول از ' . $total . ' مورد واجد شرایط پردازش می‌شود.');

        $written = 0;
        $skipped = 0;
        $bar = $this->output->createProgressBar($limit > 0 ? min($limit, $total) : $total);
        $bar->start();

        $query->chunkById(200, function ($products) use (&$written, &$skipped, $dryRun, $bar) {
            foreach ($products as $product) {
                $text = $product->generateDescription();
                $bar->advance();

                if ($text === '') {
                    $skipped++;
                    continue;
                }

                if (! $dryRun) {
                    // با Query Builder تا updated_at جابه‌جا نشود؛ این ستون در
                    // lastmod نقشه‌ی سایت می‌آید و اجرای این دستور نباید به
                    // گوگل بگوید هزاران محصول همزمان تغییر کرده‌اند.
                    \DB::table('products')->where('id', $product->id)->update(['description' => $text]);
                }

                $written++;
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info($written . ' توضیحات ساخته شد' . ($dryRun ? ' (آزمایشی — چیزی ذخیره نشد)' : '') . '.');
        if ($skipped) {
            $this->warn($skipped . ' محصول به دلیل نداشتن عنوان رد شد.');
        }
        $this->line('این متن‌ها پیش‌نویس‌اند؛ از «گزارش سلامت سئو» در پنل، آن‌ها را بازبینی و ویرایش کنید.');

        return self::SUCCESS;
    }
}
