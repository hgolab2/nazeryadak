<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$summary = DB::table('products')->selectRaw("
    COUNT(*) as total,
    SUM(CASE WHEN file_path LIKE '/upload/products/%' THEN 1 ELSE 0 END) as upload_paths,
    SUM(CASE WHEN file_path LIKE 'http%' THEN 1 ELSE 0 END) as external_paths,
    SUM(CASE WHEN file_path LIKE '/storage/%' THEN 1 ELSE 0 END) as storage_paths,
    SUM(CASE WHEN file_path IS NULL OR TRIM(file_path) = '' OR file_path = '/images/no-image.svg' THEN 1 ELSE 0 END) as empty_paths
")->first();

$brokenUpload = 0;
DB::table('products')
    ->where('file_path', 'like', '/upload/products/%')
    ->select(['file_path'])
    ->orderBy('id')
    ->chunk(500, function ($products) use (&$brokenUpload) {
        foreach ($products as $product) {
            if (!is_file(public_path(ltrim($product->file_path, '/')))) {
                $brokenUpload++;
            }
        }
    });

$samples = DB::table('products')
    ->where('file_path', 'like', '/upload/products/%')
    ->orderBy('id')
    ->select(['id', 'title', 'slug', 'sku', 'file_path'])
    ->limit(10)
    ->get();

echo json_encode(compact('summary', 'brokenUpload', 'samples'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
