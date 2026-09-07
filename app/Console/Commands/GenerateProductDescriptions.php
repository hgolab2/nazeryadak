<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ProductDescription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * پرکردن انبوه متنِ محصولاتِ بدون متن — هم توضیحات کامل، هم توضیح کوتاه.
 *
 * صفحه‌ی محصول بدون توضیحات، «محتوای نازک» است و گوگل یا ایندکسش نمی‌کند
 * یا در رتبه‌ی پایین می‌گذارد. توضیح کوتاهِ خالی همین بلا را یک پله بالاتر
 * سر صفحات فهرست می‌آورد: کارت محصول در فهرست دسته و صفحه‌ی فرود خودرو
 * فقط نام و قیمت نشان می‌دهد و آن صفحه هم چیزی برای ایندکس شدن ندارد.
 *
 *   php artisan products:describe --limit=200
 *   php artisan products:describe --force        (بازنویسی متن‌های موجود)
 *   php artisan products:describe --only=short   (فقط توضیح کوتاه)
 *   php artisan products:describe --only=seo     (فقط توضیحات متا و کلیدواژه)
 */
class GenerateProductDescriptions extends Command
{
    protected $signature = 'products:describe
                            {--limit=0 : حداکثر تعداد محصول (۰ یعنی بدون سقف)}
                            {--min=20 : توضیحات کوتاه‌تر از این تعداد کاراکتر، خالی حساب می‌شود}
                            {--only= : فقط یکی از ستون‌ها: long، short یا seo}
                            {--force : محصولاتی که متن دارند هم بازنویسی شوند}
                            {--dry-run : فقط گزارش بده، چیزی ذخیره نکن}';

    protected $description = 'ساخت پیش‌نویس توضیحات، توضیح کوتاه و فیلدهای سئوی محصولات بدون متن';

    public function handle(): int
    {
        $min = (int) $this->option('min');
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        $only = (string) $this->option('only');
        if ($only !== '' && ! in_array($only, ['long', 'short', 'seo'], true)) {
            $this->error('--only فقط long، short یا seo را می‌پذیرد.');

            return self::FAILURE;
        }

        $wantLong  = $only === '' || $only === 'long';
        $wantShort = $only === '' || $only === 'short';
        $wantSeo   = $only === '' || $only === 'seo';

        $query = Product::query()->with('categories')->orderBy('id');

        if (! $force) {
            /*
            | دو ستون، دو جای خالیِ متفاوت: محصولی که توضیحات دارد ممکن است
            | توضیح کوتاه نداشته باشد و برعکس. شرط OR می‌ماند و تصمیمِ نهایی
            | برای هر ستون داخل حلقه گرفته می‌شود، وگرنه یکی از دو ستون
            | همیشه از قلم می‌افتاد.
            */
            $query->where(function ($q) use ($min, $wantLong, $wantShort, $wantSeo) {
                if ($wantLong) {
                    $q->whereNull('description')
                      ->orWhereRaw('CHAR_LENGTH(description) < ?', [$min]);
                }
                if ($wantShort) {
                    $q->orWhereNull('short_description')
                      ->orWhereRaw("TRIM(short_description) = ''");
                }
                if ($wantSeo) {
                    $q->orWhereNull('seo_description')
                      ->orWhereRaw("TRIM(seo_description) = ''")
                      ->orWhereNull('focus_keyword')
                      ->orWhereRaw("TRIM(focus_keyword) = ''");
                }
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

        $target = $limit > 0 ? min($limit, $total) : $total;
        $this->info($target . ' محصول از ' . $total . ' مورد واجد شرایط پردازش می‌شود.');

        $longs = 0;
        $shorts = 0;
        $seos = 0;
        $skipped = 0;
        $bar = $this->output->createProgressBar($target);
        $bar->start();

        /*
        | chunkById با شرطِ «ستون خالی است» سازگار نیست: خودِ نوشتن، ردیف را
        | از نتیجه‌ی صفحه‌ی بعد بیرون می‌برد و صفحه‌ها می‌پرند. مثل
        | products:seo-describe، اول شناسه‌ها گرفته می‌شوند.
        */
        foreach (array_chunk($query->pluck('id')->all(), 200) as $chunk) {
            foreach (Product::with('categories')->whereIn('id', $chunk)->orderBy('id')->get() as $product) {
                $bar->advance();

                $update = [];

                $needsLong = $force
                    || mb_strlen(trim(strip_tags((string) $product->description))) < $min;
                if ($wantLong && $needsLong && ($text = $product->generateDescription()) !== '') {
                    $update['description'] = $text;
                    // روی نمونه هم نشانده می‌شود تا autoSeoDescription پایین‌تر
                    // از متن تازه بخواند، نه از متنِ قبلیِ دیتابیس.
                    $product->description = $text;
                }

                $needsShort = $force || trim((string) $product->short_description) === '';
                if ($wantShort && $needsShort && ($summary = ProductDescription::summary($product)) !== '') {
                    $update['short_description'] = $summary;
                }

                /*
                | توضیحات متا و کلیدواژه هر وقت که متنِ اصلی بازنویسی شود هم
                | تازه می‌شوند، نه فقط وقتی خالی باشند: ستون seo_description بر
                | متنِ خودکار اولویت دارد، پس اگر جا بماند، توضیحات متای صفحه
                | برای همیشه به متنِ قدیمی گیر می‌کند.
                */
                $needsSeo = $force
                    || isset($update['description'])
                    || trim((string) $product->seo_description) === ''
                    || trim((string) $product->focus_keyword) === '';

                if ($wantSeo && $needsSeo) {
                    $meta = $product->autoSeoDescription();
                    $keyword = ProductDescription::focusKeyword($product);

                    if ($meta !== '') {
                        $update['seo_description'] = $meta;
                    }
                    if ($keyword !== '') {
                        $update['focus_keyword'] = $keyword;
                    }
                }

                if ($update === []) {
                    $skipped++;
                    continue;
                }

                if (! $dryRun) {
                    // با Query Builder تا updated_at جابه‌جا نشود؛ این ستون در
                    // lastmod نقشه‌ی سایت می‌آید و اجرای این دستور نباید به
                    // گوگل بگوید هزاران محصول همزمان تغییر کرده‌اند.
                    DB::table('products')->where('id', $product->id)->update($update);
                }

                isset($update['description']) && $longs++;
                isset($update['short_description']) && $shorts++;
                isset($update['seo_description']) && $seos++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['ستون', 'نوشته‌شده'], [
            ['توضیحات', number_format($longs)],
            ['توضیح کوتاه', number_format($shorts)],
            ['توضیحات متا و کلیدواژه', number_format($seos)],
            ['بدون تغییر', number_format($skipped)],
        ]);

        if ($dryRun) {
            $this->warn('حالت آزمایشی — چیزی ذخیره نشد.');
        }

        $this->line('این متن‌ها پیش‌نویس‌اند؛ از «گزارش سلامت سئو» در پنل، آن‌ها را بازبینی و ویرایش کنید.');

        return self::SUCCESS;
    }
}
