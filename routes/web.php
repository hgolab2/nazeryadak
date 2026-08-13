<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\AdvertisementAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\ArticleAdminController;
use App\Http\Controllers\Admin\SettingAdminController;
use App\Http\Controllers\Admin\SeoAdminController;
use App\Http\Controllers\Admin\PaymentAdminController;

use App\Http\Controllers\SitemapController;
use App\Http\Middleware\ShareDataInFrontend;

/*
| نقشه‌ی سایت و robots. خروجی‌ها کش می‌شوند؛ برای پاک‌سازی فوری کش
| بعد از تغییرات انبوه: php artisan cache:clear
*/
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemap-static.xml', [SitemapController::class, 'static']);
Route::get('/sitemap-categories.xml', [SitemapController::class, 'categories']);
Route::get('/sitemap-articles.xml', [SitemapController::class, 'articles']);
Route::get('/sitemap-images.xml', [SitemapController::class, 'images']);
Route::get('/sitemap-products-{page}.xml', [SitemapController::class, 'products'])->where('page', '[0-9]+');
Route::get('/robots.txt', [SitemapController::class, 'robots']);

/*
| مانیفست PWA — هویت اپلیکیشن از config/seo.php خوانده می‌شود تا با بقیه‌ی
| متاتگ‌های سایت هماهنگ بماند و تغییر نام یا رنگ فقط در یک جا انجام شود.
*/
Route::get('/site.webmanifest', function () {
    $icon = fn (int $size, string $purpose = 'any') => [
        'src'     => "/assets/images/pwa/" . ($purpose === 'maskable' ? 'maskable' : 'icon') . "-{$size}.png",
        'sizes'   => "{$size}x{$size}",
        'type'    => 'image/png',
        'purpose' => $purpose,
    ];

    $shortcut = fn (string $name, string $url, string $description) => [
        'name'        => $name,
        'short_name'  => $name,
        'description' => $description,
        'url'         => $url . '?source=pwa',
        'icons'       => [$icon(192)],
    ];

    $manifest = [
        'id'               => '/',
        'name'             => seo_config('default_title', seo_site_name()),
        'short_name'       => seo_site_name(),
        'description'      => seo_config('default_description'),
        'lang'             => seo_config('language', 'fa-IR'),
        'dir'              => 'rtl',
        'start_url'        => '/?source=pwa',
        'scope'            => '/',
        'display'          => 'standalone',
        'orientation'      => 'portrait-primary',
        'background_color' => '#ffffff',
        'theme_color'      => seo_config('theme_color', '#ffffff'),
        'categories'       => ['shopping', 'business'],
        'icons'            => [
            $icon(96), $icon(128), $icon(144), $icon(152),
            $icon(192), $icon(256), $icon(384), $icon(512),
            $icon(192, 'maskable'), $icon(512, 'maskable'),
        ],
        'shortcuts'        => [
            $shortcut('فروشگاه', '/shop', 'مشاهده همه محصولات'),
            $shortcut('سبد خرید', '/cart', 'مشاهده سبد خرید'),
            $shortcut('سفارش‌های من', '/profile/orders', 'پیگیری سفارش‌ها'),
            $shortcut('تماس با ما', '/contact-us', 'راه‌های ارتباطی'),
        ],
    ];

    return response()
        ->json($manifest, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ->header('Content-Type', 'application/manifest+json; charset=UTF-8')
        ->header('Cache-Control', 'public, max-age=86400');
});

/*
| پیشنهاد زنده‌ی جعبه‌ی جستجوی هدر. عمدا بیرون از گروه Frontend است تا
| داده‌های مشترکِ صفحات (منو، سبد، دسته‌ها) برای یک پاسخ JSON کوچک
| ساخته نشود؛ این مسیر با هر حرفِ تایپ‌شده صدا زده می‌شود.
*/
Route::get('/search-suggest', [ProductController::class, 'suggest'])->name('search.suggest');

Route::group(['namespace' => 'Frontend', 'middleware' => [ShareDataInFrontend::class]], function () {
    Route::get('/payment/zarinpal/{id}', [PaymentController::class, 'request'])->name('payment.request');
    Route::get('/payment/zarinpal/callback', [PaymentController::class, 'callback'])->name('payment.callback');

    Route::get('/', [HomeController::class, 'home']);

    /*
    | این دو مسیر به ویوهایی اشاره می‌کردند که وجود ندارند و خطای ۵۰۰
    | برمی‌گرداندند؛ خطای سرور روی مسیرهای عمومی، بودجه‌ی خزش را هدر می‌دهد
    | و کیفیت سایت را نزد گوگل پایین می‌آورد. حالا با 301 به فروشگاه می‌روند
    | تا لینک‌های قدیمی هم حفظ شوند.
    */
    Route::get('view', fn() => redirect('/shop', 301));
    Route::get('search', fn(\Illuminate\Http\Request $request) => redirect(
        '/shop' . ($request->filled('q') ? '?title=' . urlencode($request->q) : ''),
        301
    ));

    Route::get('shop', [ProductController::class, 'index']);

    /*
    | آدرس تمیز دسته‌بندی: /shop/لنت-ترمز به‌جای /shop?category=لنت-ترمز
    |
    | آدرسی که کلمه‌ی کلیدی را در مسیر دارد هم برای کاربر خواناتر است و هم
    | گوگل آن را یک صفحه‌ی مستقل می‌بیند، نه نسخه‌ای فیلترشده از فروشگاه.
    | نسخه‌ی قدیمیِ query string در ProductController با 301 به همین‌جا
    | هدایت می‌شود تا لینک‌های منتشرشده از بین نروند.
    */
    Route::get('shop/{category}', [ProductController::class, 'category'])
        ->where('category', '[^/]+')
        ->name('shop.category');

    /*
    | صفحات فرود مدل خودرو و ترکیب دسته × خودرو.
    |
    | «/car/پژو-206» و «/car/پژو-206/سیستم-چرخ-و-ترمز» جای «?car_model=»
    | را می‌گیرند. نسخه‌ی query string با 301 به همین‌جا هدایت می‌شود.
    */
    Route::get('car/{car}', [ProductController::class, 'car'])
        ->where('car', '[^/]+')
        ->name('shop.car');
    Route::get('car/{car}/{category}', [ProductController::class, 'carCategory'])
        ->where(['car' => '[^/]+', 'category' => '[^/]+'])
        ->name('shop.car.category');
    Route::get('/product/fetch-image/{id}', [ProductController::class, 'fetchImage']);
    // ثبت نظر باید پیش از مسیر عمومی محصول تعریف شود؛ وگرنه «review» به
    // عنوان slug خوانده می‌شود و درخواست به show می‌رسد.
    Route::post('/product/{id}/review', [ProductController::class, 'storeReview'])->name('product.review');
    Route::get('/product/{id}/{slug?}', [ProductController::class, 'show']);
    Route::get('/about-us', [HomeController::class, 'aboutUs']);
    Route::get('/contact-us', [HomeController::class, 'contactUs']);
    Route::get('/faq', [HomeController::class, 'faq']);
    Route::get('/terms', [HomeController::class, 'terms']);
    Route::get('/privacy', [HomeController::class, 'privacy']);
    Route::get('/how-to-order', [HomeController::class, 'howToOrder']);
    Route::get('/shipping', [HomeController::class, 'shipping']);
    Route::get('/payment-methods', [HomeController::class, 'paymentMethods']);
    Route::get('/return-policy', [HomeController::class, 'returnPolicy']);

    Route::get('/blog', [BlogController::class, 'lists']);
    Route::get('/article/lists/{categoryid}', [BlogController::class, 'lists']);
    Route::get('/blog/{articleid}.html', [BlogController::class, 'view']);
    Route::get('/blog/{articleid}/{slug?}', [BlogController::class, 'view']);
    //Route::get('/blog', [HomeController::class, 'blog']);


    Route::get('/cart/data', [CartController::class, 'getCart'])->name('cart.data');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
    Route::get('/cart', [CartController::class, 'cart'])->name('cart.checkout');
    Route::post('/cart/increase', [CartController::class, 'increaseQty'])->name('cart.increase');
    Route::post('/cart/decrease', [CartController::class, 'decreaseQty'])->name('cart.decrease');
    Route::get('/order/shopping', [CheckoutController::class, 'shopping'])->name('order.shopping');
    // نمایش صفحه پرداخت
    Route::get('/order/payment/{id}', [CheckoutController::class, 'payment'])->name('order.payment');

    // ثبت نهایی سفارش
    Route::post('/order/confirm', [CheckoutController::class, 'confirmOrder'])->name('order.confirm');

    // مسیر بدون پرداخت آنلاین: ثبت سفارش و صدور پیش‌فاکتور
    Route::post('/order/place/{id}', [CheckoutController::class, 'place'])->name('order.place');
    Route::get('/order/invoice/{id}', [CheckoutController::class, 'invoice'])->name('order.invoice');

    /*
    | پیگیری سفارش بدون ورود به حساب: شماره سفارش + شماره موبایل.
    | برای مشتری‌هایی که تلفنی سفارش داده‌اند یا حوصله‌ی ورود ندارند.
    */
    Route::get('/order-tracking', [OrderTrackingController::class, 'form'])->name('order.track');
    Route::post('/order-tracking', [OrderTrackingController::class, 'track'])->name('order.track.submit');

    // ثبت رسید پرداخت (کارت‌به‌کارت/واریز) توسط مشتری
    Route::get('/profile/order/{id}/payment-receipt', [PaymentReceiptController::class, 'create'])->name('order.receipt');
    Route::post('/profile/order/{id}/payment-receipt', [PaymentReceiptController::class, 'store'])->name('order.receipt.store');

    Route::get('/profile/order/{id}', [OrderController::class, 'view'])->name('order.view');
    Route::get('/profile/orders', [OrderController::class, 'orders'])->name('order.orders');
    Route::get('/profile/orderDetail/{id}', [OrderController::class, 'orderDetails'])->name('order.orderDetails');
    Route::get('/profile/info', [OrderController::class, 'info'])->name('profile.info');
    Route::put('/profile/infoUpdate', [OrderController::class, 'infoUpdate'])->name('customer.profile.update');
    Route::get('/favorite', [ProductController::class, 'favorite'])->name('favorite');

    Route::get('/profile/password', [\App\Http\Controllers\CustomerPasswordController::class, 'edit'])->name('profile.password');
    Route::put('/profile/password', [\App\Http\Controllers\CustomerPasswordController::class, 'update'])->name('profile.password.update');
    Route::delete('/profile/password', [\App\Http\Controllers\CustomerPasswordController::class, 'destroy'])->name('profile.password.destroy');

    Route::get('/profile/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('profile.notifications');
    Route::get('/profile/notifications/count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('profile.notifications.count');

    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    Route::post('/address/add', [CheckoutController::class, 'addAddress'])->name('address.add');
    Route::post('/shipping/calc', [CheckoutController::class, 'calcShipping'])->name('shipping.calc');
    Route::post('/address/save', [CheckoutController::class, 'storeOrUpdateAddress'])->name('address.save');

    Route::get('/login', [UserController::class, 'login'])->name('login');
    Route::post('/auth/send-otp', [UserController::class, 'sendOtp'])->name('auth.sendOtp');
    Route::post('/auth/verify-otp', [UserController::class, 'verifyOtp'])->name('auth.verifyOtp');
    Route::post('/auth/login-password', [UserController::class, 'loginWithPassword'])->name('auth.loginPassword');

    Route::get('/logout', [UserController::class, 'logout']);
    Route::get('/products/favorite/{product_id}', [ProductController::class, 'addToFavorite'])->name('addToFavorite');

    Route::get('/loginAdmin', [UserController::class, 'loginAdmin'])->name('loginAdmin');
    Route::get('/dashboardAdmin', [DashboardController::class, 'index']);
    Route::post('/loginAdmin', [UserController::class, 'verifyLogin']);
    Route::group(['middleware' => ['auth']], function () {

        /* Import */
        Route::get('/admin/import', [ImportController::class, 'showForm']);
        Route::post('/admin/import', [ImportController::class, 'import'])->name('admin.import');

        /* Product */
        Route::get('/admin/product/list', [ProductAdminController::class, 'admin_list']);
        Route::get('/admin/product/create', [ProductAdminController::class, 'admin_create']);
        Route::get('/admin/product/edit/{id}', [ProductAdminController::class, 'admin_edit']);
        Route::post('/admin/product/store', [ProductAdminController::class, 'admin_store']);
        Route::put('/admin/product/update/{id}', [ProductAdminController::class, 'admin_update']);
        Route::get('/admin/product/status/{product_id}', [ProductAdminController::class, 'statusProduct']);
        Route::post('/admin/product/upload-image/{id}', [ProductAdminController::class, 'uploadImage']);
        Route::post('/admin/product/{id}/image/{imageId}/primary', [ProductAdminController::class, 'setPrimaryImage']);
        Route::delete('/admin/product/delete-image/{id}', [ProductAdminController::class, 'deleteImage']);
        Route::delete('/admin/product/{id}', [ProductAdminController::class, 'destroy']);


        /* Article */
        Route::get('/admin/article/list', [ArticleAdminController::class, 'admin_list']);
        Route::get('/admin/article/create', [ArticleAdminController::class, 'admin_create']);
        Route::get('/admin/article/edit/{id}', [ArticleAdminController::class, 'admin_edit']);
        Route::post('/admin/article/store', [ArticleAdminController::class, 'admin_store']);
        Route::put('/admin/article/update/{id}', [ArticleAdminController::class, 'admin_update']);
        Route::get('/admin/article/status/{id}', [ArticleAdminController::class, 'toggleStatus']);
        Route::delete('/admin/article/{id}', [ArticleAdminController::class, 'destroy']);

        /* Order */
        Route::get('/admin/order/list', [OrderAdminController::class, 'admin_list']);
        Route::get('/admin/order/create', [OrderAdminController::class, 'admin_create']);
        Route::get('/admin/order/edit/{id}', [OrderAdminController::class, 'admin_edit']);
        Route::post('/admin/order/store', [OrderAdminController::class, 'admin_store']);
        Route::put('/admin/order/update/{id}', [OrderAdminController::class, 'admin_update']);
        // برچسب پستی؛ مسیر گروهی قبل از {id} تا «labels» شناسه تلقی نشود
        Route::get('/admin/order/labels', [OrderAdminController::class, 'admin_labels']);
        Route::get('/admin/order/label/{id}', [OrderAdminController::class, 'admin_label']);
        Route::delete('/admin/order/{id}', [OrderAdminController::class, 'destroy']);

        /* Payment — رسیدهای پرداخت و تأیید/رد آن‌ها */
        Route::get('/admin/payment/list', [PaymentAdminController::class, 'index']);
        Route::post('/admin/payment/{id}/approve', [PaymentAdminController::class, 'approve']);
        Route::post('/admin/payment/{id}/reject', [PaymentAdminController::class, 'reject']);

        /* Customer */
        Route::get('/admin/customer/list', [OrderAdminController::class, 'admin_customer_list']);
        Route::get('/admin/customer/create', [OrderAdminController::class, 'admin_customer_create']);
        Route::get('/admin/customer/edit/{id}', [OrderAdminController::class, 'admin_customer_edit']);
        Route::post('/admin/customer/store', [OrderAdminController::class, 'admin_customer_store']);
        Route::put('/admin/customer/update/{id}', [OrderAdminController::class, 'admin_customer_update']);
        Route::delete('/admin/customer/{id}', [OrderAdminController::class, 'admin_customer_destroy']);

        /* Advertisement */
        Route::get('/admin/advertisement/create', [AdvertisementAdminController::class, 'admin_create']);
        Route::get('/admin/advertisement/edit/{id}', [AdvertisementAdminController::class, 'admin_edit']);
        Route::post('/admin/advertisement/store', [AdvertisementAdminController::class, 'admin_store']);
        Route::put('/admin/advertisement/update/{id}', [AdvertisementAdminController::class, 'admin_update']);
        Route::get('/admin/advertisement/list', [AdvertisementAdminController::class, 'admin_list']);
        Route::delete('/admin/advertisement/{id}', [AdvertisementAdminController::class, 'admin_destroy']);
        Route::get('/admin/advertisement/display/{articleid}', [AdvertisementAdminController::class, 'admin_display']);

        /* User */
        Route::get('/admin/user/create', [UserAdminController::class, 'admin_create']);
        Route::get('/admin/user/edit/{id}', [UserAdminController::class, 'admin_edit']);
        Route::post('/admin/user/store', [UserAdminController::class, 'admin_store']);
        Route::put('/admin/user/update/{id}', [UserAdminController::class, 'admin_update']);
        Route::get('/admin/user/list', [UserAdminController::class, 'admin_list']);
        Route::delete('/admin/user/{id}', [UserAdminController::class, 'admin_destroy']);

        /* Settings */
        Route::get('/admin/settings', [SettingAdminController::class, 'index'])->name('admin.settings');
        Route::put('/admin/settings', [SettingAdminController::class, 'update'])->name('admin.settings.update');

        /* SEO — گزارش سلامت، صفحات فرود، نظرات، تنظیمات */
        Route::get('/admin/seo/health', [SeoAdminController::class, 'health']);
        Route::post('/admin/seo/health/generate', [SeoAdminController::class, 'healthGenerate']);

        Route::get('/admin/seo/terms', [SeoAdminController::class, 'terms']);
        Route::get('/admin/seo/terms/edit', [SeoAdminController::class, 'termEdit']);
        Route::post('/admin/seo/terms', [SeoAdminController::class, 'termSave']);
        Route::delete('/admin/seo/terms/{id}', [SeoAdminController::class, 'termDestroy']);

        Route::get('/admin/seo/reviews', [SeoAdminController::class, 'reviews']);
        Route::put('/admin/seo/reviews/{id}/status', [SeoAdminController::class, 'reviewStatus']);
        Route::delete('/admin/seo/reviews/{id}', [SeoAdminController::class, 'reviewDestroy']);

        Route::get('/admin/seo/settings', [SeoAdminController::class, 'settings']);
        Route::put('/admin/seo/settings', [SeoAdminController::class, 'settingsUpdate']);

        /* SEO — ریدایرکت و مانیتور ۴۰۴ */
        Route::get('/admin/seo/redirects', [SeoAdminController::class, 'redirects']);
        Route::post('/admin/seo/redirects', [SeoAdminController::class, 'redirectStore']);
        Route::put('/admin/seo/redirects/{id}', [SeoAdminController::class, 'redirectUpdate']);
        Route::delete('/admin/seo/redirects/{id}', [SeoAdminController::class, 'redirectDestroy']);
        Route::get('/admin/seo/404', [SeoAdminController::class, 'notFound']);
        Route::delete('/admin/seo/404/clear', [SeoAdminController::class, 'notFoundClear']);
        Route::delete('/admin/seo/404/{id}', [SeoAdminController::class, 'notFoundDestroy']);

    });
});

