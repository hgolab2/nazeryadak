<?php

$file = 'app/Http/Controllers/HomeController.php';
$text = file_get_contents($file);
$search = <<<'PHP'
        $articles = $this->getArticle(17, 'farsi' , 'showdate' , 4);
        $products = $this->getProduct(8);
        $advertisements = $this->getAdvertisement('farsi');
PHP;
$replace = <<<'PHP'
        $articles = Article1::orderBy('showdate', 'desc')
            ->where('hidden', '0')
            ->where('deleted', '0')
            ->where('showdate', '<', date('Y-m-d H:i:s'))
            ->take(4)
            ->get();
        $products = $this->getProduct(8);
        $advertisements = $this->getAdvertisement('farsi');
PHP;
if (!str_contains($text, $search)) {
    throw new RuntimeException('home article block not found');
}
file_put_contents($file, str_replace($search, $replace, $text));
echo "fixed home articles\n";
