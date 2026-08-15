<?php

namespace App\Services;

use App\Models\EshopCategory;
use App\Models\Product;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductStockImportService
{
    /**
     * ضریب قیمت خرده‌فروشی روی قیمت فایل اکسل. در مدل محصول تعریف شده تا
     * محاسبه‌ی قیمت عمده (که از همان قیمت اکسل بازسازی می‌شود) با ایمپورت
     * از یک عدد پیروی کند.
     */
    private const PRICE_MARKUP = Product::RETAIL_MARKUP;

    /**
     * قیمت‌های فایل اکسل به ریال هستند ولی قیمت محصولات در کل سایت تومان است
     * (همان قراردادی که getShippingRules() برای تنظیمات ارسال دارد). بدون این
     * تبدیل، هر قیمتِ ایمپورت‌شده ده برابر واقعی روی سایت نمایش داده می‌شد.
     */
    private const RIAL_TO_TOMAN = 10;

    public function import(string $path, ?string $fileName = null, ?int $userId = null, bool $deactivateMissing = false): array
    {
        $skipped = 0;
        $rowsData = $this->parseRows($path, $skipped);
        $allSkus = array_keys($rowsData);
        $now = now();

        $carModelNames = array_values(array_unique(array_filter(array_column($rowsData, 'car_model'))));
        $catMap = $this->ensureCarCategories($carModelNames);

        $existing = Product::whereIn('sku', $allSkus)
            ->get()
            ->groupBy('sku');

        $imported = 0;
        $updated = 0;
        $keptProductIds = [];
        $duplicateIdsToDelete = [];

        foreach ($rowsData as $sku => $row) {
            $matches = $existing->get($sku, collect());
            $product = $this->pickProductToKeep($matches);

            $data = [
                'sku'           => $sku,
                'title'         => $row['title'],
                'slug'          => Str::slug($sku),
                'price'         => $this->sitePrice($row['sale_price']),
                'regular_price' => $this->sitePrice($row['avg_price']),
                'stock'         => $row['stock'],
                'is_active'     => $row['stock'] > 0 ? 1 : 0,
                'car_model'     => $row['car_model'],
                'updated_at'    => $now,
            ];

            if ($product) {
                // قیمت تازه، قیمتِ پیش از تخفیف است؛ اگر محصول تخفیف فعال دارد
                // باید دوباره روی همین مبنا اعمال شود وگرنه ایمپورت تخفیف را پاک می‌کند.
                $activeDiscount = (int) $product->discount_percent;
                $product->compare_at_price = null;
                $product->update($data);
                if ($activeDiscount > 0) {
                    $product->applyDiscountPercent($activeDiscount);
                    $product->save();
                }
                $updated++;

                $matches->where('id', '!=', $product->id)
                    ->pluck('id')
                    ->each(function ($id) use (&$duplicateIdsToDelete) {
                        $duplicateIdsToDelete[] = (int) $id;
                    });
            } else {
                $data['created_at'] = $now;
                $product = Product::create($data);
                $imported++;
            }

            $keptProductIds[$sku] = (int) $product->id;
        }

        $idsToDelete = $duplicateIdsToDelete;
        $idsToDelete = array_values(array_unique(array_filter($idsToDelete)));

        foreach (array_chunk($idsToDelete, 500) as $chunk) {
            DB::table('product_in_category')->whereIn('product_id', $chunk)->delete();
            DB::table('product_favorites')->whereIn('product_id', $chunk)->delete();
            Product::whereIn('id', $chunk)->delete();
        }

        $deactivated = 0;
        if ($deactivateMissing) {
            $deactivated = Product::whereNotIn('sku', $allSkus)
                ->whereNotNull('sku')
                ->where('sku', '!=', '')
                ->where(function ($query) {
                    $query->where('stock', '!=', 0)
                        ->orWhere('is_active', '!=', 0);
                })
                ->update([
                    'stock' => 0,
                    'is_active' => 0,
                    'updated_at' => $now,
                ]);
        }

        $this->syncCarLinks($rowsData, $keptProductIds, $catMap, $now);

        if ($fileName !== null && $userId !== null) {
            DB::table('import_logs')->insert([
                'user_id'    => $userId,
                'file_name'  => $fileName,
                'imported'   => $imported,
                'updated'    => $updated,
                'total'      => $imported + $updated,
                'created_at' => $now,
            ]);
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'deleted' => count($idsToDelete),
            'deactivated' => $deactivated,
            'skipped' => $skipped,
            'total' => count($rowsData),
            'categories' => count($catMap),
        ];
    }

    private function parseRows(string $path, int &$skipped = 0): array
    {
        $content = file_get_contents($path);
        $content = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $content);

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $content);
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//tbody/tr');

        $rowsData = [];
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length < 7) {
                continue;
            }

            $sku = trim($cells->item(0)->textContent);
            $title = trim($cells->item(1)->textContent);
            if ($sku === '' || $title === '' || preg_match('/[A-Za-z]$/', $sku) || !ctype_digit($sku)) {
                $skipped++;
                continue;
            }

            $rowsData[$sku] = [
                'sku'        => $sku,
                'title'      => $title,
                'car_model'  => trim($cells->item(3)->textContent),
                'stock'      => (int) trim($cells->item(4)->textContent),
                'avg_price'  => (int) floatval(str_replace(',', '', trim($cells->item(5)->textContent))),
                'sale_price' => (int) floatval(str_replace(',', '', trim($cells->item(6)->textContent))),
            ];
        }

        return $rowsData;
    }

    /** قیمت ریالیِ اکسل → قیمت تومانیِ سایت، با ضریب خرده‌فروشی. */
    private function sitePrice(int $rialPrice): int
    {
        return $rialPrice > 0
            ? (int) round($rialPrice / self::RIAL_TO_TOMAN * self::PRICE_MARKUP)
            : 0;
    }

    private function ensureCarCategories(array $carModelNames): array
    {
        $catMap = EshopCategory::whereIn('name', $carModelNames)->pluck('id', 'name')->toArray();
        $newCats = [];
        foreach ($carModelNames as $name) {
            if (!isset($catMap[$name])) {
                $newCats[] = ['name' => $name, 'slug' => Str::slug($name, '-', null)];
            }
        }

        if ($newCats) {
            EshopCategory::insert($newCats);
            $catMap = EshopCategory::whereIn('name', $carModelNames)->pluck('id', 'name')->toArray();
        }

        return $catMap;
    }

    private function pickProductToKeep($matches): ?Product
    {
        if ($matches->isEmpty()) {
            return null;
        }

        $orderedIds = $matches->pluck('id')->all();
        $orderedByOrder = DB::table('order_items')
            ->whereIn('product_id', $orderedIds)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $matches
            ->sortBy(function (Product $product) use ($orderedByOrder) {
                return [
                    in_array((int) $product->id, $orderedByOrder, true) ? 0 : 1,
                    $product->hasImage() ? 0 : 1,
                    (int) $product->id,
                ];
            })
            ->first();
    }

    private function syncCarLinks(array $rowsData, array $productMap, array $catMap, $now): void
    {
        $productIds = array_values($productMap);
        foreach (array_chunk($productIds, 500) as $chunk) {
            DB::table('product_in_category')->whereIn('product_id', $chunk)->delete();
        }

        $newLinks = [];
        foreach ($rowsData as $sku => $row) {
            if ($row['car_model'] === '') {
                continue;
            }

            $productId = $productMap[$sku] ?? null;
            $categoryId = $catMap[$row['car_model']] ?? null;
            if (!$productId || !$categoryId) {
                continue;
            }

            $newLinks[] = [
                'product_id' => $productId,
                'category_id' => $categoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($newLinks, 500) as $chunk) {
            DB::table('product_in_category')->insert($chunk);
        }
    }
}


