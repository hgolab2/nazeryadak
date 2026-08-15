<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\ProductStockImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ایمپورت موجودی و قیمت از فایل اکسل (که خروجی HTML است).
 *
 * جدول‌های محصول مهاجرت کامل ندارند، پس نسخه‌ی کوچکی از آن‌ها روی sqlite
 * حافظه‌ای ساخته می‌شود.
 */
class ProductStockImportTest extends TestCase
{
    private ?string $file = null;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('sku')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('price')->default(0);
            $table->integer('regular_price')->default(0);
            $table->integer('compare_at_price')->nullable();
            $table->integer('discount_percent')->default(0);
            $table->boolean('wholesale_enabled')->default(true);
            $table->integer('stock')->default(0);
            $table->string('car_model')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('eshop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
        });

        Schema::create('product_in_category', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->integer('category_id');
            $table->timestamps();
        });

        // ایمپورت برای انتخاب نسخه‌ی اصلی از میان SKUهای تکراری سراغش می‌رود
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('product_id');
        });
    }

    protected function tearDown(): void
    {
        if ($this->file && file_exists($this->file)) {
            unlink($this->file);
        }

        parent::tearDown();
    }

    /** فایل اکسل با همان ستون‌بندی خروجی انبار. */
    private function excel(array $rows): string
    {
        $body = '';
        foreach ($rows as $row) {
            $body .= '<tr>' . implode('', array_map(fn ($cell) => "<td>{$cell}</td>", $row)) . '</tr>';
        }

        $this->file = tempnam(sys_get_temp_dir(), 'import') ?: null;
        file_put_contents($this->file, "<table><tbody>{$body}</tbody></table>");

        return $this->file;
    }

    /**
     * قیمت‌های فایل ریال‌اند و قیمت سایت تومان؛ بدون تبدیل، هر قیمت ده برابر
     * روی صفحه‌ی محصول می‌نشست.
     */
    public function test_rial_prices_of_the_file_become_toman_prices_on_the_site(): void
    {
        // ستون‌ها: کد کالا، شرح، ــ، مدل خودرو، موجودی، قیمت میانگین، قیمت فروش
        $path = $this->excel([
            ['1001', 'لنت جلو', '', 'پژو ۲۰۶', '5', '12,000,000', '10,000,000'],
        ]);

        app(ProductStockImportService::class)->import($path);

        $product = Product::where('sku', '1001')->first();

        // ۱۰٬۰۰۰٬۰۰۰ ریال = ۱٬۰۰۰٬۰۰۰ تومان × ۱.۲
        $this->assertSame(1200000, $product->price);
        $this->assertSame(1440000, $product->regular_price);
    }

    public function test_missing_prices_stay_zero(): void
    {
        $path = $this->excel([
            ['1002', 'واشر سرسیلندر', '', 'پژو ۴۰۵', '3', '0', '0'],
        ]);

        app(ProductStockImportService::class)->import($path);

        $product = Product::where('sku', '1002')->first();

        $this->assertSame(0, $product->price);
        $this->assertSame(0, $product->regular_price);
    }

    /** ایمپورت دوباره‌ی همان فایل نباید قیمت را باز هم تقسیم کند. */
    public function test_reimport_does_not_convert_twice(): void
    {
        $path = $this->excel([
            ['1003', 'فیلتر روغن', '', 'سمند', '8', '2,500,000', '2,000,000'],
        ]);

        $importer = app(ProductStockImportService::class);
        $importer->import($path);
        $importer->import($path);

        $this->assertSame(240000, Product::where('sku', '1003')->first()->price);
    }
}
