<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

$catalogPath = storage_path('app/isaco_catalog.json');
$catalog = file_exists($catalogPath) ? json_decode(file_get_contents($catalogPath), true) : [];

function clean_text(?string $value): string
{
    $value = trim((string) $value);
    $value = str_replace(["\r", "\n", "\t"], ' ', $value);
    return preg_replace('/\s+/u', ' ', $value) ?: '';
}

function sku_base(?string $sku): ?string
{
    return preg_match('/^(\d{5})/', strtolower(clean_text($sku)), $m) ? $m[1] : null;
}

function category_label_for(int $productId): ?string
{
    $categoryId = DB::table('product_in_category')->where('product_id', $productId)->value('category_id');
    $enum = $categoryId ? ProductCategory::tryFrom((int) $categoryId) : null;
    return $enum ? $enum->label() : null;
}

function description_from_isaco(object $product, array $isaco): string
{
    $title = clean_text($product->title);
    $sku = clean_text($product->sku);
    $carModel = clean_text($product->car_model);
    $isacoTitle = clean_text($isaco['title'] ?? '');
    $isacoCode = clean_text($isaco['code'] ?? '');
    $category = category_label_for((int) $product->id);

    $lines = [];
    $lines[] = $title . ' با کد فنی ' . $sku . ' در فهرست محصولات ناظر یدک ثبت شده است. کد پایه این قطعه در کاتالوگ ایساکو ' . $isacoCode . ' و عنوان مرجع آن «' . $isacoTitle . '» است.';
    if ($carModel) {
        $lines[] = 'این محصول برای ' . $carModel . ' درج شده و پیش از خرید باید با مدل خودرو، سال تولید و قطعه قبلی تطبیق داده شود.';
    } elseif ($category) {
        $lines[] = 'این قطعه در گروه محصولات مناسب ' . $category . ' قرار گرفته و برای انتخاب دقیق‌تر باید با کد فنی و محل نصب خودرو بررسی شود.';
    } else {
        $lines[] = 'برای انتخاب دقیق‌تر، کد فنی، نام قطعه و مشخصات ظاهری آن را با قطعه نصب‌شده روی خودرو مقایسه کنید.';
    }
    $lines[] = 'تصویر محصول بر اساس تصویر مرجع موجود در کاتالوگ ایساکو ذخیره شده است. در صورت تردید درباره سازگاری، قبل از نهایی کردن سفارش از روی کد فنی ' . $sku . ' استعلام بگیرید.';

    return implode("\n\n", $lines);
}

function image_from_isaco(array $isaco): ?string
{
    $url = $isaco['image'] ?? null;
    $code = clean_text($isaco['code'] ?? '');
    if (!$url || $code === '') {
        return null;
    }

    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        $shared = "products/isaco/{$code}.{$ext}";
        if (Storage::disk('public')->exists($shared)) {
            return '/storage/' . $shared;
        }
    }

    try {
        $response = Http::timeout(20)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
            ->get($url);
        if (!$response->ok() || strlen($response->body()) < 1500) {
            return null;
        }

        $contentType = strtolower($response->header('Content-Type', ''));
        $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg';
        if (str_contains($contentType, 'png')) {
            $ext = 'png';
        } elseif (str_contains($contentType, 'webp')) {
            $ext = 'webp';
        } elseif (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) {
            $ext = 'jpg';
        }

        $filename = "products/isaco/{$code}.{$ext}";
        Storage::disk('public')->put($filename, $response->body());
        return '/storage/' . $filename;
    } catch (Throwable $e) {
        return null;
    }
}

$stats = [
    'catalog_items' => count($catalog),
    'checked' => 0,
    'matched_by_sku_prefix' => 0,
    'descriptions_updated' => 0,
    'images_updated' => 0,
    'image_failed' => 0,
    'no_match' => 0,
];

DB::table('products')
    ->where('is_active', 1)
    ->where(function ($query) {
        $query->where('description', 'not like', '%کاتالوگ ایساکو%')
            ->orWhereNull('description')
            ->orWhereRaw("TRIM(description) = ''")
            ->orWhereNull('file_path')
            ->orWhereRaw("TRIM(file_path) = ''")
            ->orWhere('file_path', '/images/no-image.svg');
    })
    ->orderBy('id')
    ->select(['id', 'title', 'sku', 'car_model', 'file_path', 'description'])
    ->chunkById(250, function ($products) use (&$stats, $catalog) {
        foreach ($products as $product) {
            $stats['checked']++;
            $base = sku_base($product->sku);
            if (!$base || !isset($catalog[$base])) {
                $stats['no_match']++;
                continue;
            }

            $stats['matched_by_sku_prefix']++;
            $isaco = $catalog[$base];
            $updates = ['updated_at' => now()];

            if (!str_contains((string) $product->description, 'کاتالوگ ایساکو')) {
                $updates['description'] = description_from_isaco($product, $isaco);
                $stats['descriptions_updated']++;
            }

            $hasImage = !empty($product->file_path) && $product->file_path !== '/images/no-image.svg';
            if (!$hasImage) {
                $path = image_from_isaco($isaco);
                if ($path) {
                    $updates['file_path'] = $path;
                    $stats['images_updated']++;
                } else {
                    $stats['image_failed']++;
                }
            }

            if (count($updates) > 1) {
                DB::table('products')->where('id', $product->id)->update($updates);
            }
        }
    });

echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
