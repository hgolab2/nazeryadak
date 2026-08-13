<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\AdvertisementAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\ArticleAdminController;

use App\Http\Middleware\ShareDataInFrontend;
Route::get('/sitemap.xml', function () {
    $urls = collect([
        ['loc' => seo_url(), 'priority' => '1.0', 'changefreq' => 'daily'],
        ['loc' => seo_url('/shop'), 'priority' => '0.9', 'changefreq' => 'daily'],
        ['loc' => seo_url('/blog'), 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['loc' => seo_url('/about-us'), 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['loc' => seo_url('/contact-us'), 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['loc' => seo_url('/faq'), 'priority' => '0.5', 'changefreq' => 'monthly'],
    ]);

    $importantShopUrls = [
        '/shop?title=ایساکو',
        '/shop?title=لنت ترمز',
        '/shop?title=فیلتر روغن',
        '/shop?title=تسمه تایم',
        '/shop?title=سنسور',
        '/shop?car_model=پژو 206',
        '/shop?car_model=پژو 405',
        '/shop?car_model=سمند',
        '/shop?car_model=دنا',
        '/shop?car_model=پراید',
    ];
    foreach ($importantShopUrls as $shopUrl) {
        $urls->push(['loc' => seo_url($shopUrl), 'priority' => '0.75', 'changefreq' => 'daily']);
    }
    foreach (\App\Enums\ProductCategory::cases() as $category) {
        $urls->push(['loc' => seo_url('/shop?category=' . $category->slug()), 'priority' => '0.75', 'changefreq' => 'daily']);
    }
    \App\Models\Product::where('is_active', 1)->select(['id', 'title', 'slug', 'updated_at'])->latest('updated_at')->chunk(500, function ($products) use (&$urls) {
        foreach ($products as $product) {
            $urls->push(['loc' => seo_url($product->url()), 'lastmod' => optional($product->updated_at)->toAtomString(), 'priority' => '0.8', 'changefreq' => 'weekly']);
        }
    });

    return response(view('sitemap', compact('urls'))->render(), 200)->header('Content-Type', 'application/xml; charset=UTF-8');
});
Route::group(['namespace' => 'Frontend', 'middleware' => [ShareDataInFrontend::class]], function () {
    Route::get('/payment/zarinpal/{id}', [PaymentController::class, 'request'])->name('payment.request');
    Route::get('/payment/zarinpal/callback', [PaymentController::class, 'callback'])->name('payment.callback');

    Route::get('/', [HomeController::class, 'home']);
    Route::get('view', [HomeController::class, 'view']);
    Route::get('search', [HomeController::class, 'search']);
    Route::get('shop', [ProductController::class, 'index']);
    Route::get('/product/fetch-image/{id}', [ProductController::class, 'fetchImage']);
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

    Route::get('/profile/order/{id}', [OrderController::class, 'view'])->name('order.view');
    Route::get('/profile/orders', [OrderController::class, 'orders'])->name('order.orders');
    Route::get('/profile/orderDetail/{id}', [OrderController::class, 'orderDetails'])->name('order.orderDetails');
    Route::get('/profile/info', [OrderController::class, 'info'])->name('profile.info');
    Route::put('/profile/infoUpdate', [OrderController::class, 'infoUpdate'])->name('customer.profile.update');
    Route::get('/favorite', [ProductController::class, 'favorite'])->name('favorite');

    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    Route::post('/address/add', [CheckoutController::class, 'addAddress'])->name('address.add');
    Route::post('/shipping/calc', [CheckoutController::class, 'calcShipping'])->name('shipping.calc');
    Route::post('/address/save', [CheckoutController::class, 'storeOrUpdateAddress'])->name('address.save');

    Route::get('/login', [UserController::class, 'login'])->name('login');
    Route::post('/auth/send-otp', [UserController::class, 'sendOtp'])->name('auth.sendOtp');
    Route::post('/auth/verify-otp', [UserController::class, 'verifyOtp'])->name('auth.verifyOtp');

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
        Route::delete('/admin/order/{id}', [OrderAdminController::class, 'destroy']);

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

    });
});

