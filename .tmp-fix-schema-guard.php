<?php

$file = 'resources/views/layout/layout.blade.php';
$text = file_get_contents($file);
$search = <<<'BLADE'
        $schemaItems = $schema ?? [];
        if (!empty($schemaItems) && array_is_list($schemaItems) === false) {
            $schemaItems = [$schemaItems];
        }
BLADE;
$replace = <<<'BLADE'
        $schemaItems = $schema ?? [];
        if (!is_array($schemaItems)) {
            $schemaItems = [];
        } elseif (!empty($schemaItems) && array_is_list($schemaItems) === false) {
            $schemaItems = [$schemaItems];
        }
        $schemaItems = array_values(array_filter($schemaItems, 'is_array'));
BLADE;
if (!str_contains($text, $search)) {
    throw new RuntimeException('schema block not found');
}
file_put_contents($file, str_replace($search, $replace, $text));
echo "fixed schema guard\n";
