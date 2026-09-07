<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ProductDescription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * بازنویسی سئویی توضیحاتِ محصولاتی که توضیحات دارند.
 *
 *   php artisan products:seo-describe --dry-run          → فقط گزارش و نمونه
 *   php artisan products:seo-describe                    → اجرای واقعی
 *   php artisan products:seo-describe --force            → ساخت دوباره‌ی متن‌ها
 *   php artisan products:seo-describe --revert           → بازگشت به متن اصلی
 *   php artisan products:seo-describe --id=4879 --show   → دیدن خروجی یک محصول
 *
 * متن اصلی پیش از اولین بازنویسی در description_source ذخیره می‌شود و منبعِ
 * همه‌ی اجراهای بعدی همان است. به این ترتیب اجرای دوباره‌ی دستور، خروجی خودش
 * را دوباره پردازش نمی‌کند (بخش‌ها روی هم انباشته نمی‌شوند) و --revert دقیقا
 * وضعیت قبل را برمی‌گرداند.
 *
 * محصولاتی که اصلا توضیحات ندارند دست‌نخورده می‌مانند؛ آن‌ها کارِ
 * products:describe هستند.
 */
class RewriteProductDescriptions extends Command
{
    protected $signature = 'products:seo-describe
        {--id=* : فقط این شناسه‌های محصول}
        {--limit=0 : حداکثر تعداد محصول (۰ یعنی بدون سقف)}
        {--force : متن‌هایی که قبلا بازنویسی شده‌اند هم دوباره ساخته شوند}
        {--revert : بازگرداندن متن اصلی از description_source}
        {--show : چاپ کامل متنِ ساخته‌شده (همراه با --id مفید است)}
        {--dry-run : فقط گزارش، بدون نوشتن در دیتابیس}';

    protected $description = 'بازنویسی سئویی توضیحات محصولات: حذف محتوای تکراری و نازک';

    public function handle(): int
    {
        return $this->option('revert') ? $this->revert() : $this->rewrite();
    }

    private function rewrite(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force  = (bool) $this->option('force');
        $limit  = (int) $this->option('limit');
        $ids    = array_filter((array) $this->option('id'));

        $query = Product::query()
            ->with('categories')
            ->where(function ($q) {
                // منبع یا هنوز در description است (اجرای اول) یا در
                // description_source (اجراهای بعدی).
                $q->whereRaw("TRIM(COALESCE(description, '')) <> ''")
                  ->orWhereRaw("TRIM(COALESCE(description_source, '')) <> ''");
            })
            ->orderBy('id');

        if ($ids) {
            $query->whereIn('id', $ids);
        }

        if (! $force && ! $ids) {
            // بدون --force فقط محصولاتی که هنوز بازنویسی نشده‌اند.
            $query->whereNull('description_source');
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('محصولی برای بازنویسی پیدا نشد. (برای ساخت دوباره از --force استفاده کنید)');

            return self::SUCCESS;
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $target = $limit > 0 ? min($limit, $total) : $total;
        $this->info(($dryRun ? 'DRY RUN — ' : '') . $target . ' محصول از ' . $total . ' مورد بازنویسی می‌شود.');

        $written = $skipped = 0;
        $lengthBefore = $lengthAfter = 0;
        $samples = [];

        $bar = $this->output->createProgressBar($target);
        $bar->start();

        $handle = function ($products) use (&$written, &$skipped, &$lengthBefore, &$lengthAfter, &$samples, $dryRun, $bar) {
            foreach ($products as $product) {
                $bar->advance();

                // منبع همیشه متن اصلی است، نه خروجیِ اجرای قبلی.
                $source = $product->description_source !== null && trim((string) $product->description_source) !== ''
                    ? (string) $product->description_source
                    : (string) $product->description;

                $html = ProductDescription::build($product, $source);

                if ($html === '' || $html === (string) $product->description) {
                    $skipped++;
                    continue;
                }

                $lengthBefore += mb_strlen(trim(strip_tags($source)));
                $lengthAfter  += mb_strlen(trim(strip_tags($html)));

                if (count($samples) < 3) {
                    $samples[] = [$product->id, $product->title, $html];
                }

                $updates = [
                    'description_source' => $source,
                    'description'        => $html,
                ];

                /*
                | توضیحات متا هم باید متنِ تازه را دنبال کند.
                |
                | ستون seo_description بر نسخه‌ی خودکار اولویت دارد، پس اگر
                | اینجا جا بماند، صفحه‌ای که متنش بازنویسی شده تا ابد توضیحات
                | متای متنِ قبلی را نشان می‌دهد.
                |
                | ولی این ستون جای بازنویسیِ دستیِ مدیر هم هست. برای اینکه
                | دست‌نوشته پاک نشود، فقط وقتی تازه می‌شود که مقدار فعلی‌اش
                | دقیقا همان چیزی باشد که از متنِ قدیمی خودکار ساخته می‌شد —
                | یعنی کسی دستکاری‌اش نکرده است.
                */
                $auto    = $product->autoSeoDescription();
                $current = trim((string) $product->seo_description);

                $product->description = $html;

                if ($current === '' || $current === $auto) {
                    $updates['seo_description'] = $product->autoSeoDescription();
                }

                if (! $dryRun) {
                    // update() مستقیم روی کوئری، تا updated_at و رویدادهای مدل
                    // برای ۱۵۹۵ ردیف بی‌دلیل به هم نریزند.
                    DB::table('products')->where('id', $product->id)->update($updates);
                }

                $written++;
            }
        };

        // chunkById با شرطِ whereNull('description_source') سازگار نیست، چون
        // خودِ نوشتن، ردیف را از نتیجه‌ی صفحه‌ی بعد بیرون می‌برد و صفحه‌ها
        // می‌پرند؛ pluck شناسه‌ها این مشکل را ندارد.
        $targetIds = $query->pluck('id')->all();
        foreach (array_chunk($targetIds, 200) as $chunk) {
            $handle(Product::with('categories')->whereIn('id', $chunk)->orderBy('id')->get());
        }

        $bar->finish();
        $this->newLine(2);

        $this->line('بازنویسی‌شده: ' . $written);
        $this->line('بدون تغییر: ' . $skipped);

        if ($written > 0) {
            $this->line('میانگین طول متن (بدون تگ): '
                . intdiv($lengthBefore, $written) . ' → ' . intdiv($lengthAfter, $written) . ' کاراکتر');
        }

        if ($this->option('show')) {
            foreach ($samples as [$id, $title, $html]) {
                $this->newLine();
                $this->line('--- #' . $id . ' ' . $title . ' ---');
                $this->line($html);
            }
        }

        if ($dryRun) {
            $this->warn('چیزی ذخیره نشد. برای اجرای واقعی، --dry-run را بردارید.');
        }

        return self::SUCCESS;
    }

    private function revert(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $ids    = array_filter((array) $this->option('id'));

        $query = Product::query()->whereRaw("TRIM(COALESCE(description_source, '')) <> ''");

        if ($ids) {
            $query->whereIn('id', $ids);
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('متن اصلی ذخیره‌شده‌ای برای بازگرداندن وجود ندارد.');

            return self::SUCCESS;
        }

        $this->warn(($dryRun ? 'DRY RUN — ' : '') . $count . ' محصول به متن اصلی برمی‌گردد.');

        if (! $dryRun) {
            $query->update([
                'description'        => DB::raw('description_source'),
                'description_source' => null,
            ]);
            $this->info('انجام شد.');
        }

        return self::SUCCESS;
    }
}
