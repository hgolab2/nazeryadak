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

function image_ext_fast(string $url, string $contentType): string
{
    $contentType = strtolower($contentType);
    if (str_contains($contentType, 'png')) return 'png';
    if (str_contains($contentType, 'webp')) return 'webp';
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? $ext : 'jpg';
}

$limit = isset($argv[1]) ? max(1, (int) $argv[1]) : 300;
$stats = ['limit' => $limit, 'checked' => 0, 'downloaded' => 0, 'failed' => 0];

$products = DB::table('products')
    ->where('file_path', 'like', 'http%')
    ->orderBy('id')
    ->select(['id', 'file_path'])
    ->limit($limit)
    ->get();

foreach ($products as $product) {
    $stats['checked']++;
    $url = trim($product->file_path);

    try {
        $response = Http::connectTimeout(4)
            ->timeout(8)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
            ->get($url);

        if (!$response->ok() || strlen($response->body()) <= 1500) {
            $stats['failed']++;
            continue;
        }

        $ext = image_ext_fast($url, $response->header('Content-Type', ''));
        $filename = $product->id . '.' . $ext;
        file_put_contents($targetDir . DIRECTORY_SEPARATOR . $filename, $response->body());

        DB::table('products')->where('id', $product->id)->update([
            'file_path' => '/upload/products/' . $filename,
            'updated_at' => now(),
        ]);
        $stats['downloaded']++;
    } catch (Throwable $e) {
        $stats['failed']++;
    }
}

echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
