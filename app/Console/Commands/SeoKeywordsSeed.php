<?php

namespace App\Console\Commands;

use App\Models\SeoKeyword;
use App\Support\CarModels;
use App\Support\PartTypes;
use Illuminate\Console\Command;

/**
 * ساخت فهرست اولیه‌ی کلیدواژه‌های هدف از ساختار خودِ سایت.
 *
 * تا وقتی سرچ کنسول داده نداده، بهترین حدس برای «چه عبارتی مهم است» همان
 * چیزی است که انبار می‌گوید: هر «قطعه × خودرو» که به اندازه‌ی کافی محصول
 * دارد، یک عبارت جستجوی واقعی است («لنت ترمز پژو 206») و صفحه‌ی فرودش هم
 * از قبل هست. اولویت از تعداد محصول می‌آید: هرچه بیشتر، احتمال جستجو و
 * فروش بالاتر.
 *
 * عبارت‌هایی که از قبل در فهرست باشند دست نمی‌خورند (هدف و اولویتِ دستی
 * مدیر محفوظ می‌ماند).
 */
class SeoKeywordsSeed extends Command
{
    protected $signature = 'seo:keywords-seed
                            {--min-combo=5 : کمینه‌ی محصول برای عبارت «قطعه × خودرو»}
                            {--check : بعد از ساخت، صفحه‌ی هر عبارت جدید بررسی شود}';

    protected $description = 'ساخت فهرست اولیه‌ی کلیدواژه‌های هدف از قطعات و خودروهای موجود در انبار';

    public function handle(): int
    {
        $created = [];
        $minCombo = max(1, (int) $this->option('min-combo'));

        /* قطعه × خودرو — پرارزش‌ترین قالب */
        $cars = CarModels::all();
        foreach (PartTypes::carCounts() as $partSlug => $carCounts) {
            $partName = PartTypes::name($partSlug);
            if (! $partName) {
                continue;
            }

            foreach ($carCounts as $carSlug => $count) {
                if ($count < $minCombo || ! isset($cars[$carSlug])) {
                    continue;
                }

                $created[] = $this->add(
                    $partName . ' ' . $cars[$carSlug]['name'],
                    '/part/' . $partSlug . '/' . $carSlug,
                    $count >= 20 ? 1 : 2,
                );
            }
        }

        /* نوع قطعه به‌تنهایی */
        foreach (PartTypes::counts() as $partSlug => $count) {
            if (! PartTypes::isIndexable($partSlug)) {
                continue;
            }
            $created[] = $this->add((string) PartTypes::name($partSlug), '/part/' . $partSlug, $count >= 40 ? 1 : 2);
        }

        /* مدل خودرو: «لوازم یدکی X» */
        foreach ($cars as $carSlug => $car) {
            if (! CarModels::isIndexable($carSlug)) {
                continue;
            }
            $created[] = $this->add('لوازم یدکی ' . $car['name'], '/car/' . $carSlug, $car['count'] >= 100 ? 1 : 2);
        }

        $new = array_values(array_filter($created));
        $this->info(count($new) . ' عبارت جدید اضافه شد (' . (count($created) - count($new)) . ' مورد از قبل بود).');

        if ($this->option('check') && $new) {
            $this->call('seo:keywords-check', ['--id' => array_map(fn ($k) => $k->id, $new)]);
        }

        return self::SUCCESS;
    }

    /** null یعنی از قبل بود. */
    private function add(string $keyword, string $url, int $priority): ?SeoKeyword
    {
        $row = SeoKeyword::findOrCreateFromKeyword($keyword, $url, $priority);

        return $row->wasRecentlyCreated ? $row : null;
    }
}
