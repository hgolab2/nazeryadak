<?php
$file='resources/views/product/show.blade.php';
$content = shell_exec('git show HEAD:resources/views/product/show.blade.php');
if ($content === null || $content === '') { fwrite(STDERR, "git show failed\n"); exit(1); }
file_put_contents($file, $content);
echo "restored from git show\n";
