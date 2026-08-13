<?php

$files = [
    'routes/web.php',
    'app/Enums/ProductCategory.php',
    'app/Console/Commands/CategorizeProducts.php',
    'app/Http/Controllers/Admin/ImportController.php',
    'resources/views/product/list.blade.php',
    'resources/views/product/product_card.blade.php',
    'resources/views/article/show.blade.php',
    'resources/views/layout/layout.blade.php',
    'app/Models/Product.php',
    'app/Models/Article1.php',
    'app/Http/Controllers/BlogController.php',
];

foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }

    $original = file_get_contents($file);
    if (preg_match('/Ø|Ù|Û|Ú|Â|â/u', $original) !== 1) {
        continue;
    }

    $fixed = mb_convert_encoding($original, 'Windows-1252', 'UTF-8');
    if ($fixed !== $original && preg_match('//u', $fixed)) {
        file_put_contents($file, $fixed);
        echo "fixed {$file}\n";
    }
}
