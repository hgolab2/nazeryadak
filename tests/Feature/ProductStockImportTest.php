<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\ProductCategorizer;
use App\Services\ProductStockImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->string('unit', 50)->nullable();
            $table->unsignedInteger('pack_qty')->nullable();
            $table->string('car_model')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ایمپورت هر تغییر قیمت را اینجا ثبت می‌کند
        Schema::create('product_price_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->bigInteger('price');
            $table->string('source', 20)->default('import');
            $table->timestamp('created_at')->nullable();
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

        // ۱۰٬۰۰۰٬۰۰۰ ریال = ۱٬۰۰۰٬۰۰۰ تومان × ۱.۳
        $this->assertSame(1300000, $product->price);
        $this->assertSame(1560000, $product->regular_price);
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

        // ۲٬۰۰۰٬۰۰۰ ریال = ۲۰۰٬۰۰۰ تومان × ۱.۳
        $this->assertSame(260000, Product::where('sku', '1003')->first()->price);
    }

    /**
     * واحد شمارش ستون سومِ فایل است و تا امروز دور ریخته می‌شد.
     */
    public function test_unit_of_measure_is_stored(): void
    {
        $path = $this->excel([
            ['1004', 'شمع', 'عدد', 'پژو ۲۰۶', '10', '100,000', '100,000'],
            ['1005', 'لنت عقب', 'دست 4 عددي', 'سمند', '4', '200,000', '200,000'],
            ['1006', 'روغن موتور', 'گالن 4ليتري', 'دنا', '2', '300,000', '300,000'],
        ]);

        app(ProductStockImportService::class)->import($path);

        $this->assertSame('عدد', Product::where('sku', '1004')->first()->unit);
        $this->assertSame('دست 4 عددي', Product::where('sku', '1005')->first()->unit);
        $this->assertSame('گالن 4ليتري', Product::where('sku', '1006')->first()->unit);
    }

    /**
     * تعداد در بسته از متنِ واحد درمی‌آید. برای واحد حجمی باید null بماند:
     * «گالن ۴ليتري» یعنی چهار لیتر، نه چهار عدد.
     */
    public function test_pack_quantity_is_derived_from_the_unit_text(): void
    {
        $path = $this->excel([
            ['1004', 'شمع', 'عدد', 'پژو ۲۰۶', '10', '100,000', '100,000'],
            ['1005', 'لنت عقب', 'دست 4 عددي', 'سمند', '4', '200,000', '200,000'],
            ['1006', 'روغن موتور', 'گالن 4ليتري', 'دنا', '2', '300,000', '300,000'],
        ]);

        app(ProductStockImportService::class)->import($path);

        $this->assertSame(1, Product::where('sku', '1004')->first()->pack_qty);
        $this->assertSame(4, Product::where('sku', '1005')->first()->pack_qty);
        $this->assertNull(Product::where('sku', '1006')->first()->pack_qty);
    }

    /** هر ایمپورت باید قیمت روز را در تاریخچه ثبت کند. */
    public function test_import_records_a_price_history_point(): void
    {
        $path = $this->excel([
            ['1007', 'دیسک ترمز', 'عدد', 'سمند', '5', '1,000,000', '1,000,000'],
        ]);

        app(ProductStockImportService::class)->import($path);

        $product = Product::where('sku', '1007')->first();
        $points  = DB::table('product_price_history')->where('product_id', $product->id)->get();

        $this->assertCount(1, $points);
        $this->assertSame(130000, (int) $points->first()->price);
        $this->assertSame('import', $points->first()->source);
    }

    /**
     * ایمپورت روی هزاران محصول اجرا می‌شود و اکثرشان قیمتشان عوض نشده؛
     * بدون این بررسی، نمودار پر از نقطه‌ی تکراری می‌شد.
     */
    public function test_unchanged_price_does_not_add_a_second_point(): void
    {
        $path = $this->excel([
            ['1008', 'واترپمپ', 'عدد', 'دنا', '5', '1,000,000', '1,000,000'],
        ]);

        $importer = app(ProductStockImportService::class);
        $importer->import($path);
        $importer->import($path);

        $product = Product::where('sku', '1008')->first();

        $this->assertSame(1, DB::table('product_price_history')->where('product_id', $product->id)->count());
    }

    /**
     * ایمپورت فقط دسته‌ی مدل خودرو را بازمی‌سازد. پیش‌تر تمام ردیف‌های
     * product_in_category محصول را پاک می‌کرد و مجموعه‌بندی قطعه از بین می‌رفت.
     */
    public function test_import_keeps_the_part_grouping_of_existing_products(): void
    {
        $importer = app(ProductStockImportService::class);
        $importer->import($this->excel([
            ['1010', 'فیلتر روغن', 'عدد', 'سمند', '5', '100,000', '100,000'],
        ]));

        $product = Product::where('sku', '1010')->first();

        // مجموعه‌بندی دستی مدیر: «شاسی و بدنه» به‌جای چیزی که کلیدواژه می‌گوید
        DB::table('product_in_category')
            ->where('product_id', $product->id)
            ->whereIn('category_id', ProductCategorizer::groupingIds())
            ->delete();
        DB::table('product_in_category')->insert([
            'product_id' => $product->id,
            'category_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $importer->import($this->excel([
            ['1010', 'فیلتر روغن', 'عدد', 'سمند', '7', '120,000', '120,000'],
        ]));

        $groupings = DB::table('product_in_category')
            ->where('product_id', $product->id)
            ->whereIn('category_id', ProductCategorizer::groupingIds())
            ->pluck('category_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertSame([3], $groupings);
    }

    /** و محصول تازه باید همان‌جا مجموعه‌بندی بگیرد، نه اینکه بی‌دسته بماند. */
    public function test_new_products_get_a_part_grouping(): void
    {
        app(ProductStockImportService::class)->import($this->excel([
            ['1011', 'لنت ترمز جلو', 'دست', 'پژو ۲۰۶', '5', '100,000', '100,000'],
        ]));

        $product = Product::where('sku', '1011')->first();

        $this->assertSame(6, (int) DB::table('product_in_category')
            ->where('product_id', $product->id)
            ->whereIn('category_id', ProductCategorizer::groupingIds())
            ->value('category_id'));
    }

    /** دسته‌ی مدل خودرو باید تکراری نشود وقتی همان فایل دوباره ایمپورت می‌شود. */
    public function test_reimport_does_not_duplicate_category_links(): void
    {
        $importer = app(ProductStockImportService::class);
        $path = $this->excel([
            ['1012', 'رادیاتور', 'عدد', 'دنا', '5', '100,000', '100,000'],
        ]);

        $importer->import($path);
        $importer->import($path);

        $product = Product::where('sku', '1012')->first();

        $this->assertSame(2, DB::table('product_in_category')
            ->where('product_id', $product->id)
            ->count());
    }

    /** ولی تغییر واقعی قیمت باید نقطه‌ی تازه بسازد. */
    public function test_changed_price_adds_a_new_point(): void
    {
        $importer = app(ProductStockImportService::class);

        $importer->import($this->excel([
            ['1009', 'رادیاتور', 'عدد', 'تارا', '5', '1,000,000', '1,000,000'],
        ]));
        $importer->import($this->excel([
            ['1009', 'رادیاتور', 'عدد', 'تارا', '5', '1,000,000', '2,000,000'],
        ]));

        $product = Product::where('sku', '1009')->first();
        $prices  = DB::table('product_price_history')
            ->where('product_id', $product->id)
            ->orderBy('id')
            ->pluck('price')
            ->map(fn ($p) => (int) $p)
            ->all();

        $this->assertSame([130000, 260000], $prices);
    }
}
