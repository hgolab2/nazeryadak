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
];

function looks_bad(string $text): bool
{
    return preg_match('/Ã|Â|Ø|Ù|Û|â/u', $text) === 1;
}

function repair_once(string $text): string
{
    return mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
}

foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }

    $text = file_get_contents($file);
    $fixed = $text;
    for ($i = 0; $i < 3 && looks_bad($fixed); $i++) {
        $candidate = repair_once($fixed);
        if ($candidate === '' || $candidate === $fixed) {
            break;
        }
        $fixed = $candidate;
    }

    if ($fixed !== $text && preg_match('//u', $fixed)) {
        file_put_contents($file, $fixed);
        echo "fixed {$file}\n";
    }
}
