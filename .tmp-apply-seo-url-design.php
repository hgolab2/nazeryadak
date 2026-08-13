<?php

function put(string $file, string $text): void
{
    file_put_contents($file, $text);
    echo "wrote {$file}\n";
}

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
    'app/Helpers/Helper.php',
    "function seo_store_schema(): array\n{",
    "function seo_slug(?string \$text, string \$fallback = 'item'): string\n{\n    \$text = trim((string) \$text);\n    \$text = strtr(\$text, [\n        'ي' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه', 'ؤ' => 'و', 'إ' => 'ا', 'أ' => 'ا', 'آ' => 'آ',\n        \"\\u{200C}\" => '-', \"\\u{200F}\" => '', \"\\u{200E}\" => '',\n    ]);\n    \$text = preg_replace('/[^\\p{L}\\p{N}]+/u', '-', \$text);\n    \$text = trim(preg_replace('/-+/u', '-', \$text), '-');\n\n    return \$text !== '' ? mb_strtolower(\$text) : \$fallback;\n}\n\nfunction seo_store_schema(): array\n{"
);

replace_once(
    'app/Models/Product.php',
    "return '/product/' . \$this->id . '/' . (\$this->slug ?: Str::slug(\$this->title, '-'));",
    "\$slug = seo_slug(trim((\$this->sku ? \$this->sku . '-' : '') . \$this->title), (string) \$this->id);\n        return '/product/' . \$this->id . '/' . \$slug;"
);

replace_once(
    'app/Models/Article1.php',
    "return '/blog/'.\$this->articleid.'.html';",
    "\$slug = seo_slug(\$this->titr ?: ('article-' . \$this->articleid), (string) \$this->articleid);\n        return '/blog/' . \$this->articleid . '/' . \$slug;"
);

replace_once(
    'routes/web.php',
    "Route::get('/blog/{articleid}.html', [BlogController::class, 'view']);",
    "Route::get('/blog/{articleid}.html', [BlogController::class, 'view']);\n    Route::get('/blog/{articleid}/{slug?}', [BlogController::class, 'view']);"
);

replace_once(
    'app/Http/Controllers/BlogController.php',
    'public function view(Request $request , $articleid)',
    'public function view(Request $request , $articleid, $slug = null)'
);

replace_once(
    'app/Http/Controllers/BlogController.php',
    "if(is_array(\$result))\n        {\n            return View('article.show' , \$result);",
    "if(is_array(\$result))\n        {\n            if (\$slug !== null && \$request->path() !== ltrim(\$result['info']->getUrl(), '/')) {\n                return redirect(\$result['info']->getUrl(), 301);\n            }\n            if (str_ends_with(\$request->path(), '.html')) {\n                return redirect(\$result['info']->getUrl(), 301);\n            }\n            return View('article.show' , \$result);"
);

replace_once(
    'routes/web.php',
    "    \$importantShopUrls = [\n        '/shop?title=Ø§ÛŒØ³Ø§Ú©Ùˆ',\n        '/shop?title=Ù„Ù†Øª ØªØ±Ù…Ø²',\n        '/shop?title=ÙÛŒÙ„ØªØ± Ø±ÙˆØºÙ†',\n        '/shop?title=ØªØ³Ù…Ù‡ ØªØ§ÛŒÙ…',\n        '/shop?title=Ø³Ù†Ø³ÙˆØ±',\n        '/shop?car_model=Ù¾Ú˜Ùˆ 206',\n        '/shop?car_model=Ù¾Ú˜Ùˆ 405',\n        '/shop?car_model=Ø³Ù…Ù†Ø¯',\n        '/shop?car_model=Ø¯Ù†Ø§',\n        '/shop?car_model=Ù¾Ø±Ø§ÛŒØ¯',\n    ];",
    "    \$importantShopUrls = [\n        '/shop?title=ایساکو',\n        '/shop?title=لنت ترمز',\n        '/shop?title=فیلتر روغن',\n        '/shop?title=تسمه تایم',\n        '/shop?title=سنسور',\n        '/shop?car_model=پژو 206',\n        '/shop?car_model=پژو 405',\n        '/shop?car_model=سمند',\n        '/shop?car_model=دنا',\n        '/shop?car_model=پراید',\n    ];"
);

replace_once('routes/web.php', '// Ù†Ù…Ø§ÛŒØ´ ØµÙØ­Ù‡ Ù¾Ø±Ø¯Ø§Ø®Øª', '// نمایش صفحه پرداخت');
replace_once('routes/web.php', '// Ø«Ø¨Øª Ù†Ù‡Ø§ÛŒÛŒ Ø³ÙØ§Ø±Ø´', '// ثبت نهایی سفارش');

replace_once(
    'resources/views/product/show.blade.php',
    '<main>',
    '<main class="product-show-page">'
);

replace_once(
    'resources/views/product/show.blade.php',
    '<div class="product-content">',
    '<div class="product-content product-hero-panel">'
);

replace_once(
    'resources/views/product/show.blade.php',
    '<style>
.product-description-html img',
    '<style>
.product-show-page .breadcrumb { margin-bottom: 0; }
.product-hero-panel { background:#fff; border:1px solid #eef1f4; border-radius:8px; padding:18px; box-shadow:0 10px 30px rgba(15,23,42,.05); }
.product-show-page .add-cart-box { position:sticky; top:16px; border-radius:8px; border:1px solid #e7edf3; box-shadow:0 12px 28px rgba(15,23,42,.06); }
.product-show-page #product-main-image { width:100%; min-height:280px; background:#fafafa; border-radius:8px; }
.product-show-page .product-details h1 { font-size:1.25rem !important; }
.product-show-page .product-tab-content { background:#fff; border:1px solid #eef1f4; border-radius:8px; margin-top:18px; }
@media (max-width: 991px) {
    .product-hero-panel { padding:12px; }
    .product-show-page .add-cart-box { position:static; margin-top:14px; }
}
.product-description-html img'
);

replace_once(
    'resources/views/index.blade.php',
    '<main class="home-page">',
    '<main class="home-page home-polished">'
);

if (!str_contains(file_get_contents('resources/views/index.blade.php'), '.home-polished .home-hero')) {
    replace_once(
        'resources/views/index.blade.php',
        '@section(\'js\')',
        '<style>
.home-polished .home-hero { padding:28px 0 18px; background:linear-gradient(180deg,#f7fafc 0,#fff 100%); }
.home-polished .home-hero-grid { align-items:stretch; gap:22px; }
.home-polished .home-hero-content { padding:26px 0; }
.home-polished .home-hero-content h1 { max-width:760px; line-height:1.65; }
.home-polished .home-isaco-card { border:1px solid #e7edf3; border-radius:8px; box-shadow:0 12px 30px rgba(15,23,42,.06); background:#fff; }
.home-polished .home-section { margin-top:24px; }
.home-polished .section-header { align-items:center; border-bottom:1px solid #edf1f5; padding-bottom:10px; margin-bottom:14px; }
.home-polished .home-category-grid a,
.home-polished .home-car-card,
.home-polished .blog-card-new { border-radius:8px; border:1px solid #edf1f5; box-shadow:0 8px 22px rgba(15,23,42,.04); }
.home-polished .home-help-panel { border-radius:8px; background:#0f766e; color:#fff; }
.home-polished .home-help-panel p { color:rgba(255,255,255,.85); }
@media (max-width: 767px) {
    .home-polished .home-hero { padding-top:12px; }
    .home-polished .home-hero-content { padding:12px 0; }
    .home-polished .home-hero-content h1 { font-size:1.35rem; }
}
</style>

@section(\'js\')'
    );
}
