<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$articles = DB::table('article1')->where('deleted', 0)->orderBy('articleid')->get(['articleid','titr']);
$first20 = $articles->take(20)->values();
$rest = $articles->slice(20)->pluck('articleid')->all();
$dir = public_path('imgArticle/upload/2026/08');
$now = now()->format('Y-m-d H:i:s');

foreach ($first20 as $idx => $article) {
    $n = $idx + 1;
    $plainName = sprintf('generated-auto-part-%02d.jpg', $n);
    $plainPath = $dir . DIRECTORY_SEPARATOR . $plainName;
    if (!is_file($plainPath)) {
        throw new RuntimeException("Missing generated image: {$plainPath}");
    }
    $size = getimagesize($plainPath) ?: [900, 520];
    $fileId = DB::table('files')->insertGetId([
        'title' => $article->titr,
        'description' => 'تصویر اختصاصی مقاله ' . $article->titr,
        'filetype' => 'image/jpeg',
        'extension' => 'jpg',
        'filepath' => $plainName,
        'savedate' => $now,
        'savedby' => 3,
        'filesize' => filesize($plainPath),
        'grouptype' => 1,
        'width' => $size[0],
        'height' => $size[1],
        'direction' => null,
        'persian' => 0,
    ]);
    $finalPath = $dir . DIRECTORY_SEPARATOR . $fileId . '_' . $plainName;
    rename($plainPath, $finalPath);
    DB::table('article1')->where('articleid', $article->articleid)->update([
        'image' => (string) $fileId,
        'imageId' => $fileId,
        'updatetime' => $now,
    ]);
}

if ($rest) {
    DB::table('article1')->whereIn('articleid', $rest)->update([
        'image' => null,
        'imageId' => null,
        'updatetime' => $now,
    ]);
}

echo 'with_image=' . DB::table('article1')->where('deleted',0)->whereNotNull('image')->count() . PHP_EOL;
echo 'without_image=' . DB::table('article1')->where('deleted',0)->whereNull('image')->count() . PHP_EOL;
foreach (DB::table('article1')->where('deleted',0)->orderBy('articleid')->limit(22)->get(['articleid','titr','image']) as $a) {
    echo $a->articleid . ' | image=' . ($a->image ?? 'NULL') . ' | ' . $a->titr . PHP_EOL;
}