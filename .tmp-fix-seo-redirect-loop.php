<?php

function replace_once(string $file, string $search, string $replace): void
{
    $text = file_get_contents($file);
    if (!str_contains($text, $search)) {
        throw new RuntimeException("Pattern not found in {$file}");
    }
    file_put_contents($file, str_replace($search, $replace, $text));
    echo "updated {$file}\n";
}

replace_once(
    'app/Http/Controllers/ProductController.php',
    "if (\$slug !== null && request()->path() !== ltrim(\$model->url(), '/')) {\n            return redirect(\$model->url(), 301);\n        }",
    "\$currentPath = rawurldecode(request()->path());\n        if (\$slug !== null && \$currentPath !== ltrim(\$model->url(), '/')) {\n            return redirect(\$model->url(), 301);\n        }"
);

replace_once(
    'app/Http/Controllers/BlogController.php',
    "if (\$slug !== null && \$request->path() !== ltrim(\$result['info']->getUrl(), '/')) {\n                return redirect(\$result['info']->getUrl(), 301);\n            }\n            if (str_ends_with(\$request->path(), '.html')) {",
    "\$currentPath = rawurldecode(\$request->path());\n            if (\$slug !== null && \$currentPath !== ltrim(\$result['info']->getUrl(), '/')) {\n                return redirect(\$result['info']->getUrl(), 301);\n            }\n            if (str_ends_with(\$request->path(), '.html')) {"
);
