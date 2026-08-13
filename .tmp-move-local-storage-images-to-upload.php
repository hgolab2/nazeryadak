<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$targetDir = public_path('upload/products');
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0775, true);
}

$stats = [
    'checked_storage_paths' => 0,
    'already_in_upload' => 0,
    'moved' => 0,
    'copied_existing_partial' => 0,
    'missing_source' => 0,
];

DB::table('products')
    ->where('file_path', 'like', '/storage/%')
    ->orderBy('id')
    ->select(['id', 'file_path'])
    ->chunkById(300, function ($products) use (&$stats, $targetDir) {
        foreach ($products as $product) {
            $stats['checked_storage_paths']++;

            $relative = preg_replace('#^/storage/#', '', $product->file_path);
            $source = storage_path('app/public/' . $relative);
            $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION)) ?: 'jpg';
            $filename = $product->id . '.' . $ext;
            $target = $targetDir . DIRECTORY_SEPARATOR . $filename;
            $publicPath = '/upload/products/' . $filename;

            if (is_file($target)) {
                DB::table('products')->where('id', $product->id)->update([
                    'file_path' => $publicPath,
                    'updated_at' => now(),
                ]);
                $stats['already_in_upload']++;
                continue;
            }

            if (!is_file($source)) {
                $stats['missing_source']++;
                continue;
            }

            if (@rename($source, $target)) {
                DB::table('products')->where('id', $product->id)->update([
                    'file_path' => $publicPath,
                    'updated_at' => now(),
                ]);
                $stats['moved']++;
            } else {
                $stats['missing_source']++;
            }
        }
    });

echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
