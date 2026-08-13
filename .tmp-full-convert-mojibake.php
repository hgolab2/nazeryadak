<?php
foreach (['resources/views/layout/layout.blade.php','resources/views/product/show.blade.php'] as $file) {
    $text = file_get_contents($file);
    $fixed = iconv('UTF-8', 'Windows-1252//IGNORE', $text);
    file_put_contents($file, $fixed);
    echo "converted $file\n";
}
