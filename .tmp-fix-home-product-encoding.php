<?php

$files = [
    'routes/web.php',
    'resources/views/index.blade.php',
    'resources/views/product/show.blade.php',
    'resources/views/product/list.blade.php',
    'resources/views/product/product_card.blade.php',
    'resources/views/layout/layout.blade.php',
    'resources/views/article/show.blade.php',
    'app/Http/Controllers/Admin/ImportController.php',
];

foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }

    $text = file_get_contents($file);
    $fixed = $text;
    for ($i = 0; $i < 2 && preg_match('/Ø|Ù|Û|Ú|Ã|Â|â/u', $fixed); $i++) {
        $candidate = mb_convert_encoding($fixed, 'Windows-1252', 'UTF-8');
        if ($candidate === '' || $candidate === $fixed || !preg_match('//u', $candidate)) {
            break;
        }
        $fixed = $candidate;
    }

    if ($fixed !== $text && preg_match('//u', $fixed)) {
        file_put_contents($file, $fixed);
        echo "fixed {$file}\n";
    }
}
