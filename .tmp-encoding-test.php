<?php
$samples = [
    'double' => 'Ã˜Â®Ã˜Â±Ã›Å’Ã˜Â¯',
    'single' => 'Ø®Ø±ÛŒØ¯',
];

foreach ($samples as $name => $sample) {
    $converted = mb_convert_encoding($sample, 'Windows-1252', 'UTF-8');
    echo $name . ': ' . $converted . PHP_EOL;
}
