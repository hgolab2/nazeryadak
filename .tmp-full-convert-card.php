<?php
foreach (['resources/views/product/product_card.blade.php'] as $file) {
 $text=file_get_contents($file);
 file_put_contents($file, mb_convert_encoding($text,'Windows-1252','UTF-8'));
}
