<?php
$w = 900;
$h = 320;
$img = imagecreatetruecolor($w, $h);
imagesavealpha($img, true);
$white = imagecolorallocate($img, 255, 255, 255);
$blue = imagecolorallocate($img, 45, 121, 189);
$darkBlue = imagecolorallocate($img, 0, 69, 124);
$orange = imagecolorallocate($img, 245, 124, 0);
$shadow = imagecolorallocatealpha($img, 0, 0, 0, 116);
imagefill($img, 0, 0, $white);

// shield
$cx = 190;
$top = 55;
$shield = [
    $cx, $top,
    $cx + 92, $top + 26,
    $cx + 92, $top + 105,
    $cx + 66, $top + 162,
    $cx, $top + 205,
    $cx - 66, $top + 162,
    $cx - 92, $top + 105,
    $cx - 92, $top + 26,
];
imagefilledpolygon($img, $shield, $blue);

// horse-like white marks, clean and simple
imagefilledpolygon($img, [$cx-55,$top+95, $cx-22,$top+48, $cx+44,$top+22, $cx+7,$top+68, $cx+8,$top+120, $cx-22,$top+108], $white);
imagefilledpolygon($img, [$cx-73,$top+138, $cx-25,$top+86, $cx+49,$top+112, $cx+2,$top+139, $cx-40,$top+161], $white);
imagesetthickness($img, 8);
imagearc($img, $cx+2, $top+126, 135, 105, 18, 168, $white);
imagesetthickness($img, 1);

$fontBold = __DIR__ . '/public/fonts/vazir/Vazir-Bold.ttf';
$fontRegular = __DIR__ . '/public/fonts/vazir/Vazir.ttf';
if (!file_exists($fontBold)) { $fontBold = __DIR__ . '/public/assets/font/Vazir.woff'; }

imagettftext($img, 72, 0, 350, 128, $blue, $fontBold, 'ISACO');
imagettftext($img, 58, 0, 386, 215, $blue, $fontBold, 'ایساکو');
imagesetthickness($img, 9);
imageline($img, 350, 245, 742, 245, imagecolorallocate($img, 232, 241, 250));
imageline($img, 600, 245, 742, 245, $orange);

$out = __DIR__ . '/public/assets/images/isaco-logo.png';
imagepng($img, $out, 6);
imagedestroy($img);
echo $out . PHP_EOL;
