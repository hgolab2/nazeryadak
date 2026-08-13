<?php
$file='resources/views/product/show.blade.php';
$text=file_get_contents($file);
$fixed=mb_convert_encoding($text,'Windows-1252','UTF-8');
file_put_contents($file,$fixed);
echo "converted show mojibake bytes\n";
