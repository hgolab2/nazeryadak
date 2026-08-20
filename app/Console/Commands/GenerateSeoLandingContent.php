<?php

namespace App\Console\Commands;

use App\Enums\ProductCategory;
use App\Models\Product;
use App\Models\SeoTerm;
use App\Support\CarModels;
use App\Support\SeoContent;
use Illuminate\Console\Command;

/**
 * پرکردن جدول seo_terms از روی دانشِ SeoContent + داده‌ی واقعی انبار.
 *
 *   php artisan seo:landing                 → فقط صفحاتی که هنوز متن ندارند
 *   php artisan seo:landing --refresh       → بازنویسی متن‌های تولیدشده‌ی قبلی
 *   php artisan seo:landing --force         → بازنویسی همه، حتی متن دستیِ مدیر
 *   php artisan seo:landing --only=car      → فقط یک نوع (category|car|car_category)
 *   php artisan seo:landing --dry-run       → فقط گزارش، بدون نوشتن در دیتابیس
 *
 * متنی که مدیر در پنل نوشته باشد، پیش‌فرض دست‌نخورده می‌ماند: ستون
 * generated نشان می‌دهد رکورد را این دستور ساخته یا آدم. بدون این تفکیک،
 * اجرای دوباره‌ی دستور، ساعت‌ها کار دستی را پاک می‌کرد.
 */
class GenerateSeoLandingContent extends Command
{
    protected $signature = 'seo:landing
        {--refresh : متن‌های تولیدشده‌ی قبلی هم بازنویسی شوند}
        {--force : متن دستیِ مدیر هم بازنویسی شود}
        {--only= : فقط یک نوع: category|car|car_category}
        {--dry-run : فقط گزارش، بدون ذخیره}';

    protected $description = 'ساخت محتوای سئوی صفحات فرود (دسته‌بندی، مدل خودرو و ترکیب دسته × خودرو)';

    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $missing = 0;

    public function handle(): int
    {
        $only   = $this->option('only');
        $dryRun = (bool) $this->option('dry-run');

        // فهرست خودروها و شمارش‌ها از کش می‌آیند؛ اگر انبار تازه به‌روز شده
        // باشد، متنِ ساخته‌شده باید بر اساس موجودی امروز باشد نه شش ساعت پیش.
        CarModels::forgetCache();

        if (! $only || $only === SeoTerm::TYPE_CATEGORY) {
            $this->buildCategories($dryRun);
        }

        if (! $only || $only === SeoTerm::TYPE_CAR) {
            $this->buildCars($dryRun);
        }

        if (! $only || $only === SeoTerm::TYPE_CAR_CATEGORY) {
            $this->buildCombos($dryRun);
        }

        SeoTerm::forgetCache();

        $this->newLine();
        $this->info(sprintf(
            'ساخته شد: %d | به‌روزرسانی شد: %d | دست‌نخورده: %d | بدون دانش ثبت‌شده: %d%s',
            $this->created, $this->updated, $this->skipped, $this->missing,
            $dryRun ? ' (اجرای آزمایشی — چیزی ذخیره نشد)' : ''
        ));

        if ($this->missing > 0) {
            $this->warn('برای مدل‌هایی که دانش اختصاصی ندارند متن ساخته نشد؛ آن‌ها را در app/Support/SeoContent.php اضافه کنید یا دستی در پنل بنویسید.');
        }

        return self::SUCCESS;
    }

    /* ------------------------------------------------------------ دسته‌بندی */

    private function buildCategories(bool $dryRun): void
    {
        $this->line('<comment>دسته‌بندی‌ها</comment>');

        foreach (ProductCategory::cases() as $case) {
            $content = SeoContent::forCategory($case->slug());

            if ($content === null) {
                $this->missing++;
                $this->line('  - ' . $case->label() . ' — دانش ثبت‌شده ندارد');
                continue;
            }

            $this->store(SeoTerm::TYPE_CATEGORY, $case->slug(), $content, $dryRun);
        }
    }

    /* ---------------------------------------------------------- مدل خودرو */

    private function buildCars(bool $dryRun): void
    {
        $this->line('<comment>مدل‌های خودرو</comment>');

        $combos = CarModels::comboCounts();

        foreach (CarModels::all() as $slug => $car) {
            if (! SeoContent::hasCar($slug)) {
                $this->missing++;
                $this->line('  - ' . $car['name'] . ' — دانش ثبت‌شده ندارد');
                continue;
            }

            // پرمحصول‌ترین گروه‌های همین خودرو؛ جمله‌ی «بیشتر از همه در ...»
            // از روی موجودی واقعی ساخته می‌شود نه از یک فهرست ثابت.
            $byCategory = $combos[$slug] ?? [];
            arsort($byCategory);
            $topCategories = [];
            foreach (array_slice($byCategory, 0, 3, true) as $categoryId => $count) {
                if ($count > 0 && $case = ProductCategory::tryFrom((int) $categoryId)) {
                    $topCategories[] = $case->label();
                }
            }

            $content = SeoContent::forCar($slug, $car['name'], (int) $car['count'], $topCategories);

            if ($content === null) {
                $this->missing++;
                continue;
            }

            $this->store(SeoTerm::TYPE_CAR, $slug, $content, $dryRun);
        }
    }

    /* ------------------------------------------------------- دسته × خودرو */

    private function buildCombos(bool $dryRun): void
    {
        $this->line('<comment>ترکیب دسته × خودرو</comment>');

        $combos = CarModels::comboCounts();
        $cars   = CarModels::all();
        $bar    = $this->output->createProgressBar(count($cars) * count(ProductCategory::cases()));
        $bar->start();

        foreach ($cars as $carSlug => $car) {
            if (! SeoContent::hasCar($carSlug)) {
                $bar->advance(count(ProductCategory::cases()));
                $this->missing += count(ProductCategory::cases());
                continue;
            }

            foreach (ProductCategory::cases() as $case) {
                $bar->advance();

                $count = (int) ($combos[$carSlug][$case->value] ?? 0);

                // ترکیبی که هیچ محصولی ندارد، صفحه‌ی خالی است؛ نه متن
                // می‌خواهد و نه ارزش یک ردیف در دیتابیس را دارد.
                if ($count === 0) {
                    continue;
                }

                $content = SeoContent::forCarCategory(
                    $carSlug,
                    $car['name'],
                    $case->slug(),
                    $count,
                    $this->sampleTitles($car['name'], $case->value)
                );

                if ($content === null) {
                    continue;
                }

                $this->store(
                    SeoTerm::TYPE_CAR_CATEGORY,
                    $carSlug . '/' . $case->slug(),
                    $content,
                    $dryRun
                );
            }
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * نام چند محصول واقعیِ همین ترکیب.
     *
     * همین چند نام، متنِ هر صفحه‌ی ترکیبی را از بقیه متمایز می‌کند؛ بدون آن،
     * ۲۳۱ صفحه فقط جای دو کلمه با هم فرق می‌کردند.
     *
     * @return array<int, string>
     */
    private function sampleTitles(string $carName, int $categoryId): array
    {
        try {
            return Product::query()
                ->where('is_active', 1)
                ->where('car_model', $carName)
                ->whereHas('categories', fn ($q) => $q->where('category_id', $categoryId))
                ->orderByRaw('CHAR_LENGTH(title)')
                ->limit(3)
                ->pluck('title')
                ->map(fn ($title) => trim((string) $title))
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /* ---------------------------------------------------------- ذخیره‌سازی */

    /** @param array<string, mixed> $content */
    private function store(string $type, string $slug, array $content, bool $dryRun): void
    {
        $term = SeoTerm::where('type', $type)->where('slug', $slug)->first();

        if ($term && ! $this->option('force')) {
            // متن دستیِ مدیر هرگز بدون --force بازنویسی نمی‌شود.
            if (! $term->generated) {
                $this->skipped++;
                return;
            }

            if (! $this->option('refresh')) {
                $this->skipped++;
                return;
            }
        }

        if ($dryRun) {
            $term ? $this->updated++ : $this->created++;
            return;
        }

        $payload = [
            'name'            => $content['name'],
            'heading'         => $content['heading'],
            'intro'           => $content['intro'],
            'body'            => $content['body'],
            'faq'             => json_encode($content['faq'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'seo_title'       => $content['seo_title'],
            'seo_description' => $content['seo_description'],
            'focus_keyword'   => $content['focus_keyword'],
            'robots_index'    => $content['robots_index'],
            'is_active'       => true,
            'generated'       => true,
        ];

        if ($term) {
            $term->update($payload);
            $this->updated++;
        } else {
            SeoTerm::create($payload + ['type' => $type, 'slug' => $slug]);
            $this->created++;
        }
    }
}
