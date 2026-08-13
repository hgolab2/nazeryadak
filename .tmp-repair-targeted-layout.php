<?php
$files = [
 'resources/views/layout/layout.blade.php',
 'resources/views/product/product_card.blade.php',
 'resources/views/product/show.blade.php',
];
foreach ($files as $file) {
    $text = file_get_contents($file);
    $fixed = preg_replace_callback('/[ØÙÛÚÂÃ][^\r\n<>{}\[\]]*/u', function ($m) {
        $s = $m[0];
        $converted = @mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
        return $converted ?: $s;
    }, $text);
    file_put_contents($file, $fixed);
    echo "fixed $file\n";
}
