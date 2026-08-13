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

function looks_bad_text(string $text): bool
{
    return preg_match('/Ã|Â|â€|â€™|â€œ|â€Œ/u', $text) === 1
        || preg_match('/(?:Ø|Ù|Û|Ú)[\x{0080}-\x{00FF}\x{0152}\x{02DC}]/u', $text) === 1;
}

foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }

    $original = file_get_contents($file);
    if (!looks_bad_text($original)) {
        continue;
    }

    $fixed = $original;
    for ($i = 0; $i < 3 && looks_bad_text($fixed); $i++) {
        $candidate = mb_convert_encoding($fixed, 'Windows-1252', 'UTF-8');
        if ($candidate === '' || $candidate === $fixed || !preg_match('//u', $candidate)) {
            break;
        }
        $fixed = $candidate;
    }

    if ($fixed !== $original && preg_match('//u', $fixed)) {
        file_put_contents($file, $fixed);
        echo "fixed {$file}\n";
    }
}
