<?php

$file = 'app/Http/Controllers/HomeController.php';
$text = file_get_contents($file);
$text = str_replace(
    "public function getArticle(\$categoryid , \$lang = 'farsi' , \$sort = 'showdate' , \$count)",
    "public function getArticle(\$categoryid, \$count, \$lang = 'farsi', \$sort = 'showdate')",
    $text
);
file_put_contents($file, $text);
echo "fixed getArticle signature\n";
