<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * شمارش بازدید محصول و ریل «پیشنهاد ویژه»ی ساخته‌شده روی آن.
 *
 * مثل بقیه‌ی تست‌های محصول، جدول‌ها دستی روی sqlite حافظه‌ای ساخته می‌شوند
 * چون جدول products مهاجرت کامل ندارد.
 */
class ProductViewCountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Product::flushContactPriceCache();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('price')->default(0);
            $table->integer('discount_percent')->default(0);
            $table->boolean('is_special_offer')->default(false);
            // مدل Product مقدار پیش‌فرض این ستون را روی نمونه‌ی تازه می‌نشاند
            $table->boolean('wholesale_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();
        });

        Schema::create('product_in_category', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->integer('category_id');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->string('path')->nullable();
            $table->string('alt')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
        });

        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->date('viewed_on');
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'viewed_on']);
        });
    }

    private function product(array $attributes = []): Product
    {
        return Product::create($attributes + [
            'title'     => 'قطعه آزمایشی',
            'price'     => 1000000,
            'file_path' => '/images/part.webp',
            'is_active' => true,
        ]);
    }

    /** درخواستی با نشست، مثل درخواست واقعیِ یک مرورگر. */
    private function browserRequest(string $userAgent = 'Mozilla/5.0 (Windows NT 10.0) Chrome/120'): Request
    {
        $request = Request::create('/product/1', 'GET', [], [], [], ['HTTP_USER_AGENT' => $userAgent]);
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));

        return $request;
    }

    private function views(Product $product, ?string $day = null): int
    {
        return (int) DB::table('product_views')
            ->where('product_id', $product->id)
            ->where('viewed_on', $day ?? today()->toDateString())
            ->value('hits');
    }

    public function test_a_visit_is_stored_for_today_and_added_to_the_total(): void
    {
        $product = $this->product();

        ProductView::record($product, $this->browserRequest());

        $this->assertSame(1, $this->views($product));
        $this->assertSame(1, (int) $product->fresh()->views_count);
    }

    public function test_refreshing_the_page_in_the_same_session_is_counted_once(): void
    {
        $product = $this->product();
        $request = $this->browserRequest();

        ProductView::record($product, $request);
        ProductView::record($product, $request);
        ProductView::record($product, $request);

        $this->assertSame(1, $this->views($product));
    }

    public function test_a_different_visitor_adds_to_the_same_day_row(): void
    {
        $product = $this->product();

        ProductView::record($product, $this->browserRequest());
        ProductView::record($product, $this->browserRequest());

        $this->assertSame(2, $this->views($product));
        $this->assertSame(1, DB::table('product_views')->count());
    }

    public function test_crawlers_are_not_counted(): void
    {
        $product = $this->product();

        ProductView::record($product, $this->browserRequest('Mozilla/5.0 (compatible; Googlebot/2.1)'));
        ProductView::record($product, $this->browserRequest('curl/8.4.0'));

        $this->assertSame(0, DB::table('product_views')->count());
        $this->assertSame(0, (int) $product->fresh()->views_count);
    }

    public function test_special_offer_rail_ranks_by_views_of_the_last_two_days(): void
    {
        $cold   = $this->product(['title' => 'بی‌بازدید', 'discount_percent' => 40]);
        $stale  = $this->product(['title' => 'پربازدید قدیمی', 'discount_percent' => 30]);
        $recent = $this->product(['title' => 'پربازدید تازه', 'discount_percent' => 10]);

        // بازدید سه‌روزِ‌پیش نباید به حساب بیاید، حتی با عدد بزرگ
        $this->seedViews($stale, today()->subDays(3)->toDateString(), 900);
        $this->seedViews($recent, today()->subDay()->toDateString(), 20);
        $this->seedViews($recent, today()->toDateString(), 5);

        $rail = (new HomeController())->getSpecialOfferProducts(12);

        $this->assertSame(
            [$recent->id, $cold->id, $stale->id],
            $rail->pluck('id')->all()
        );
    }

    public function test_rail_skips_small_discounts_and_products_without_a_picture(): void
    {
        $ok       = $this->product(['discount_percent' => 4]);
        $exactly3 = $this->product(['discount_percent' => 3]);
        $noPhoto  = $this->product(['discount_percent' => 40, 'file_path' => '']);
        $inactive = $this->product(['discount_percent' => 40, 'is_active' => false]);
        $this->product(['discount_percent' => 40, 'file_path' => '/images/no-image.svg']);

        // پربازدیدترین‌ها هم اگر شرط‌ها را نداشته باشند نمی‌آیند
        $this->seedViews($exactly3, today()->toDateString(), 500);
        $this->seedViews($noPhoto, today()->toDateString(), 500);
        $this->seedViews($inactive, today()->toDateString(), 500);

        $rail = (new HomeController())->getSpecialOfferProducts(12);

        $this->assertSame([$ok->id], $rail->pluck('id')->all());
    }

    public function test_a_product_whose_photo_comes_from_the_panel_still_qualifies(): void
    {
        $product = $this->product(['discount_percent' => 20, 'file_path' => null]);
        DB::table('product_images')->insert([
            'product_id' => $product->id,
            'path'       => '/uploads/part.webp',
            'is_primary' => true,
        ]);

        $rail = (new HomeController())->getSpecialOfferProducts(12);

        $this->assertSame([$product->id], $rail->pluck('id')->all());
    }

    private function seedViews(Product $product, string $day, int $hits): void
    {
        DB::table('product_views')->insert([
            'product_id' => $product->id,
            'viewed_on'  => $day,
            'hits'       => $hits,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
