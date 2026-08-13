<?php
$folder = __DIR__ . '/public/imgArticle/upload/2026/08';
$files = glob($folder . '/156*.{jpg,jpeg,png}', GLOB_BRACE);
$files = array_values(array_filter($files, function ($path) {
    $id = (int) strtok(basename($path), '_');
    return $id >= 156173 && $id <= 156222;
}));
usort($files, fn ($a, $b) => (int) strtok(basename($a), '_') <=> (int) strtok(basename($b), '_'));

$thumbW = 180;
$thumbH = 110;
$labelH = 34;
$cols = 5;
$rows = (int) ceil(count($files) / $cols);
$sheet = imagecreatetruecolor($cols * $thumbW, $rows * ($thumbH + $labelH));
$white = imagecolorallocate($sheet, 255, 255, 255);
$black = imagecolorallocate($sheet, 0, 0, 0);
imagefill($sheet, 0, 0, $white);

foreach ($files as $i => $path) {
    $info = getimagesize($path);
    $src = $info[2] === IMAGETYPE_PNG ? imagecreatefrompng($path) : imagecreatefromjpeg($path);
    $ratio = min($thumbW / imagesx($src), $thumbH / imagesy($src));
    $w = (int) round(imagesx($src) * $ratio);
    $h = (int) round(imagesy($src) * $ratio);
    $x = ($i % $cols) * $thumbW + (int) (($thumbW - $w) / 2);
    $y = intdiv($i, $cols) * ($thumbH + $labelH);
    imagecopyresampled($sheet, $src, $x, $y, 0, 0, $w, $h, imagesx($src), imagesy($src));
    imagedestroy($src);
    imagestring($sheet, 3, ($i % $cols) * $thumbW + 4, $y + $thumbH + 4, basename($path), $black);
}

imagejpeg($sheet, __DIR__ . '/.tmp-article-contact.jpg', 90);
imagedestroy($sheet);
echo __DIR__ . '/.tmp-article-contact.jpg' . PHP_EOL;
