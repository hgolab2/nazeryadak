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

$codes = Product::whereRaw("sku REGEXP '^[0-9]{5}'")
    ->selectRaw('LEFT(sku, 5) as code')
    ->distinct()
    ->orderBy('code')
    ->pluck('code')
    ->all();

$doneCodes = 0;
$noImages = 0;
$failed = 0;
$updatedProducts = 0;
$insertedImages = 0;

foreach ($codes as $code) {
    try {
        $response = Http::timeout(30)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get('https://isaco.ir/%D9%82%D8%B7%D8%B9%D8%A7%D8%AA/' . $code);

        if (!$response->ok()) {
            $failed++;
            echo "{$code}: http " . $response->status() . PHP_EOL;
            continue;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $response->body());
        $xpath = new DOMXPath($dom);

        $title = trim(preg_replace('/\s+/u', ' ', $xpath->query('//h1')->item(0)?->textContent ?? ''));
        if ($title === '') {
            $title = $code;
        }

        $imageUrls = [];
        foreach ($xpath->query('//img') as $img) {
            $src = $img->getAttribute('src') ?: $img->getAttribute('data-src') ?: '';
            if ($src === '' || !str_contains($src, '/parts/images/' . $code . '/')) {
                continue;
            }
            $imageUrls[] = str_starts_with($src, 'http') ? $src : 'https://isaco.ir' . $src;
        }
        $imageUrls = array_values(array_unique($imageUrls));

        if (!$imageUrls) {
            $noImages++;
            echo "{$code}: no images" . PHP_EOL;
            continue;
        }

        $dir = __DIR__ . '/public/upload/products/isaco/' . $code;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $paths = [];
        foreach ($imageUrls as $index => $remoteUrl) {
            $ext = pathinfo(parse_url($remoteUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'gallery-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.' . strtolower($ext);
            $local = $dir . '/' . $filename;

            if (!file_exists($local) || filesize($local) < 1000) {
                $imageResponse = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get($remoteUrl);

                if (!$imageResponse->ok() || strlen($imageResponse->body()) < 1000) {
                    continue;
                }

                file_put_contents($local, $imageResponse->body());
            }

            if (file_exists($local) && filesize($local) >= 1000) {
                $paths[] = '/upload/products/isaco/' . $code . '/' . $filename;
            }
        }

        if (!$paths) {
            $failed++;
            echo "{$code}: image download failed" . PHP_EOL;
            continue;
        }

        $products = Product::where('sku', 'like', $code . '%')->get();
        foreach ($products as $product) {
            DB::table('product_images')->where('product_id', $product->id)->delete();
            foreach ($paths as $index => $path) {
                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'path' => $path,
                    'alt' => $title,
                    'is_primary' => $index === 0 ? 1 : 0,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $insertedImages++;
            }

            $product->update([
                'file_path' => $paths[0],
                'isaco_code' => $code,
                'isaco_url' => 'https://isaco.ir/قطعات/' . $code,
            ]);
            $updatedProducts++;
        }

        $doneCodes++;
        echo "{$code}: products=" . $products->count() . " images=" . count($paths) . PHP_EOL;
        usleep(150000);
    } catch (Throwable $e) {
        $failed++;
        echo "{$code}: " . $e->getMessage() . PHP_EOL;
    }
}

echo "SUMMARY codes={$doneCodes} products={$updatedProducts} images={$insertedImages} no_images={$noImages} failed={$failed}" . PHP_EOL;
