<?php

function replace_in_file(string $file, string $search, string $replace): void
{
    $text = file_get_contents($file);
    if (!str_contains($text, $search)) {
        throw new RuntimeException("Pattern not found in {$file}");
    }
    file_put_contents($file, str_replace($search, $replace, $text));
    echo "updated {$file}\n";
}

replace_in_file(
    'app/Services/ProductStockImportService.php',
    "Product::whereNotIn('sku', \$allSkus)\n                ->where(function (\$query) {",
    "Product::whereNotIn('sku', \$allSkus)\n                ->whereNotNull('sku')\n                ->where('sku', '!=', '')\n                ->where(function (\$query) {"
);

replace_in_file(
    'app/Http/Controllers/Admin/ImportController.php',
    'return back()->with(\'success\', "Ø¹Ù…Ù„ÛŒØ§Øª Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø§Ù†Ø¬Ø§Ù… Ø´Ø¯. {$result[\'imported\']} Ù…Ø­ØµÙˆÙ„ Ø¬Ø¯ÛŒØ¯ Ø§Ø¶Ø§ÙÙ‡ Ùˆ {$result[\'updated\']} Ù…Ø­ØµÙˆÙ„ Ø¨Ø±ÙˆØ²Ø±Ø³Ø§Ù†ÛŒ Ø´Ø¯. {$result[\'deleted\']} Ø±Ú©ÙˆØ±Ø¯ ØªÚ©Ø±Ø§Ø±ÛŒ Ø­Ø°Ù Ø´Ø¯. {$result[\'deactivated\']} Ù…Ø­ØµÙˆÙ„ Ø®Ø§Ø±Ø¬ Ø§Ø² ÙØ§ÛŒÙ„ Ù†Ø§Ù…ÙˆØ¬ÙˆØ¯ Ø´Ø¯. {$result[\'categories\']} Ø¯Ø³ØªÙ‡â€ŒØ¨Ù†Ø¯ÛŒ Ø®ÙˆØ¯Ø±Ùˆ Ø§ÛŒØ¬Ø§Ø¯/Ø¨Ø±Ø±Ø³ÛŒ Ø´Ø¯.");',
    'return back()->with(\'success\', "عملیات با موفقیت انجام شد. {$result[\'imported\']} محصول جدید اضافه و {$result[\'updated\']} محصول بروزرسانی شد. {$result[\'deleted\']} رکورد تکراری حذف شد. {$result[\'deactivated\']} محصول دارای SKU که خارج از فایل بود ناموجود شد. {$result[\'categories\']} دسته‌بندی خودرو ایجاد/بررسی شد.");'
);

replace_in_file(
    'app/Http/Controllers/Admin/ImportController.php',
    "return back()->with('error', 'Ø®Ø·Ø§ Ø¯Ø± Ù¾Ø±Ø¯Ø§Ø²Ø´ ÙØ§ÛŒÙ„: ' . \$e->getMessage());",
    "return back()->with('error', 'خطا در پردازش فایل: ' . \$e->getMessage());"
);
