<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$targetDir = public_path('upload/products');
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0775, true);
}

function extension_from_url_or_type(string $url, string $contentType = ''): string
{
    $contentType = strtolower($contentType);
    if (str_contains($contentType, 'png')) {
        return 'png';
    }
    if (str_contains($contentType, 'webp')) {
        return 'webp';
    }
    if (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) {
        return 'jpg';
    }

    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? $ext : 'jpg';
}

$stats = [
    'checked' => 0,
    'copied_from_storage' => 0,
    'downloaded_from_url' => 0,
    'already_in_upload' => 0,
    'failed' => 0,
];

DB::table('products')
    ->whereNotNull('file_path')
    ->whereRaw("TRIM(file_path) <> ''")
    ->where('file_path', '<>', '/images/no-image.svg')
    ->orderBy('id')
    ->select(['id', 'file_path'])
    ->chunkById(200, function ($products) use (&$stats, $targetDir) {
        foreach ($products as $product) {
            $stats['checked']++;
            $current = trim($product->file_path);

            if (str_starts_with($current, '/upload/products/')) {
                $absolute = public_path(ltrim($current, '/'));
                if (is_file($absolute)) {
                    $stats['already_in_upload']++;
                    continue;
                }
            }

            $savedPublicPath = null;

            if (str_starts_with($current, '/storage/')) {
                $relative = preg_replace('#^/storage/#', '', $current);
                $source = storage_path('app/public/' . $relative);
                if (is_file($source)) {
                    $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION)) ?: 'jpg';
                    $filename = $product->id . '.' . $ext;
                    copy($source, $targetDir . DIRECTORY_SEPARATOR . $filename);
                    $savedPublicPath = '/upload/products/' . $filename;
                    $stats['copied_from_storage']++;
                }
            } elseif (str_starts_with($current, 'http')) {
                try {
                    $response = Http::timeout(25)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
                        ->get($current);

                    if ($response->ok() && strlen($response->body()) > 1500) {
                        $ext = extension_from_url_or_type($current, $response->header('Content-Type', ''));
                        $filename = $product->id . '.' . $ext;
                        file_put_contents($targetDir . DIRECTORY_SEPARATOR . $filename, $response->body());
                        $savedPublicPath = '/upload/products/' . $filename;
                        $stats['downloaded_from_url']++;
                    }
                } catch (Throwable $e) {
                    $savedPublicPath = null;
                }
            }

            if ($savedPublicPath) {
                DB::table('products')->where('id', $product->id)->update([
                    'file_path' => $savedPublicPath,
                    'updated_at' => now(),
                ]);
            } else {
                $stats['failed']++;
            }
        }
    });

echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
