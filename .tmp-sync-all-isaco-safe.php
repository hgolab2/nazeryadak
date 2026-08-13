<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

set_time_limit(0);
ini_set('memory_limit', '1024M');
DB::disableQueryLog();

$apply = in_array('--apply', $argv, true);
$limit = 0;
$onlyCode = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = (int) substr($arg, 8);
    }
    if (str_starts_with($arg, '--code=')) {
        $onlyCode = substr($arg, 7);
    }
}

$codes = $onlyCode
    ? [$onlyCode]
    : Product::whereRaw("sku REGEXP '^[0-9]{5}'")
        ->selectRaw('LEFT(sku, 5) as code')
        ->distinct()
        ->orderBy('code')
        ->pluck('code')
        ->all();
if ($limit > 0) {
    $codes = array_slice($codes, 0, $limit);
}

$badTerms = [
    'صفحه اصلی',
    'راهنمای مشتریان',
    'حـقـوق مـشـتریـان',
    'نمایندگی',
    'نمایندگی‌ها',
    'فروشگاه های فعال',
    'فروشگاه‌ها',
    'رسانه',
    'رسانهاخبار',
    'درباره ما',
    'منوهای اصلی',
    'تماس باما',
    'ایران، تهران',
    'ورود | ثبت نام',
    'موبـایل اپـلیکیـشن',
];

$stats = [
    'codes_seen' => 0,
    'codes_with_images' => 0,
    'codes_with_clean_text' => 0,
    'products_touched' => 0,
    'images_inserted' => 0,
    'descriptions_updated' => 0,
    'descriptions_skipped' => 0,
    'no_images' => 0,
    'failed' => 0,
];

foreach ($codes as $code) {
    $stats['codes_seen']++;
    try {
        $products = Product::where('sku', 'like', $code . '%')->get();
        if ($products->isEmpty()) {
            continue;
        }

        $content = fetchIsaco($code, $badTerms);
        if (!$content) {
            $stats['failed']++;
            echo "{$code}: no page/content" . PHP_EOL;
            continue;
        }

        $imagePaths = downloadImages($code, $content['gallery']);
        if ($imagePaths) {
            $stats['codes_with_images']++;
        } else {
            $stats['no_images']++;
        }

        $cleanDescription = isCleanDescription($content['description_html'], $badTerms);
        if ($cleanDescription) {
            $stats['codes_with_clean_text']++;
        }

        if ($apply) {
            foreach ($products as $product) {
                $update = [
                    'isaco_code' => $code,
                    'isaco_url' => $content['url'],
                ];
                if ($imagePaths) {
                    $update['file_path'] = $imagePaths[0];
                }
                if ($cleanDescription) {
                    $update['short_description'] = $content['short_description'];
                    $update['description'] = $content['description_html'];
                }
                $product->update($update);

                if ($imagePaths) {
                    DB::table('product_images')->where('product_id', $product->id)->delete();
                    foreach ($imagePaths as $index => $path) {
                        DB::table('product_images')->insert([
                            'product_id' => $product->id,
                            'path' => $path,
                            'alt' => $content['title'],
                            'is_primary' => $index === 0 ? 1 : 0,
                            'sort_order' => $index + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $stats['images_inserted']++;
                    }
                }

                $stats['products_touched']++;
                if ($cleanDescription) {
                    $stats['descriptions_updated']++;
                } else {
                    $stats['descriptions_skipped']++;
                }
            }
        }

        echo "{$code}: products={$products->count()} images=" . count($imagePaths) . ' text=' . ($cleanDescription ? 'clean' : 'skip') . PHP_EOL;
        usleep(150000);
    } catch (Throwable $e) {
        $stats['failed']++;
        echo "{$code}: " . $e->getMessage() . PHP_EOL;
    }
}

echo 'SUMMARY ' . json_encode($stats, JSON_UNESCAPED_UNICODE) . PHP_EOL;

function fetchIsaco(string $code, array $badTerms): ?array
{
    $response = Http::timeout(30)
        ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
        ->get('https://isaco.ir/%D9%82%D8%B7%D8%B9%D8%A7%D8%AA/' . $code);

    if (!$response->ok()) {
        return null;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $response->body());
    $xpath = new DOMXPath($dom);

    $title = trim(preg_replace('/\s+/u', ' ', $xpath->query('//h1')->item(0)?->textContent ?? $code));
    $subtitle = trim(preg_replace('/\s+/u', ' ', $xpath->query('//h2')->item(0)?->textContent ?? $title));

    $gallery = [];
    foreach ($xpath->query('//img') as $img) {
        $src = $img->getAttribute('src') ?: $img->getAttribute('data-src') ?: '';
        if ($src !== '' && str_contains($src, '/parts/images/' . $code . '/')) {
            $gallery[] = str_starts_with($src, 'http') ? $src : 'https://isaco.ir' . $src;
        }
    }
    $gallery = array_values(array_unique($gallery));

    $texts = [];
    foreach ($xpath->query('//p|//li') as $node) {
        $text = trim(preg_replace('/\s+/u', ' ', $node->textContent));
        if (mb_strlen($text) < 25 || containsBadTerm($text, $badTerms)) {
            continue;
        }
        if (preg_match('/^\.(mui|css|swiper|slick|Mui)/i', $text)) {
            continue;
        }
        $texts[] = $text;
    }
    $texts = array_values(array_unique(array_slice($texts, 0, 12)));

    $description = '';
    if ($texts) {
        $description = '<h2>' . e($subtitle ?: $title) . '</h2>';
        foreach ($texts as $text) {
            $description .= '<p>' . e($text) . '</p>';
        }
    }

    return [
        'url' => 'https://isaco.ir/قطعات/' . $code,
        'title' => $title,
        'short_description' => $texts[0] ?? null,
        'description_html' => $description,
        'gallery' => $gallery,
    ];
}

function downloadImages(string $code, array $urls): array
{
    $dir = __DIR__ . '/public/upload/products/isaco/' . $code;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $paths = [];
    foreach ($urls as $index => $remoteUrl) {
        $ext = pathinfo(parse_url($remoteUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'gallery-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.' . strtolower($ext);
        $local = $dir . '/' . $filename;
        if (!file_exists($local) || filesize($local) < 1000) {
            $response = Http::timeout(30)->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($remoteUrl);
            if (!$response->ok() || strlen($response->body()) < 1000) {
                continue;
            }
            file_put_contents($local, $response->body());
        }
        if (file_exists($local) && filesize($local) >= 1000) {
            $paths[] = '/upload/products/isaco/' . $code . '/' . $filename;
        }
    }

    return $paths;
}

function isCleanDescription(?string $html, array $badTerms): bool
{
    if (!$html || mb_strlen(strip_tags($html)) < 80) {
        return false;
    }

    return !containsBadTerm($html, $badTerms);
}

function containsBadTerm(string $text, array $badTerms): bool
{
    foreach ($badTerms as $term) {
        if (str_contains($text, $term)) {
            return true;
        }
    }

    return false;
}
