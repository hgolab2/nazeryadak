<?php

$enum = <<<'PHP'
<?php

namespace App\Enums;

enum ProductCategory: int
{
    case ENGINE = 1;
    case CONSUMABLES = 2;
    case CHASSIS_BODY = 3;
    case GEARBOX = 4;
    case FUEL_SYSTEM = 5;
    case BRAKE_SUSPENSION = 6;
    case ELECTRICAL = 7;
    case COOLING = 8;
    case EXHAUST = 9;
    case INTERIOR = 10;
    case OTHER = 11;

    public function label(): string
    {
        return match ($this) {
            self::ENGINE => 'موتور و اجزای متعلقه',
            self::CONSUMABLES => 'قطعات مصرفی',
            self::CHASSIS_BODY => 'شاسی و بدنه',
            self::GEARBOX => 'گیربکس و دیفرانسیل',
            self::FUEL_SYSTEM => 'سوخت‌رسانی و جرقه',
            self::BRAKE_SUSPENSION => 'چرخ، ترمز و جلوبندی',
            self::ELECTRICAL => 'برق و سنسورهای خودرو',
            self::COOLING => 'سیستم خنک‌کننده',
            self::EXHAUST => 'اگزوز و تهویه',
            self::INTERIOR => 'تزئینات داخلی و خارجی',
            self::OTHER => 'سایر قطعات',
        };
    }

    public function slug(): string
    {
        return match ($this) {
            self::ENGINE => 'موتور-و-اجزای-متعلقه',
            self::CONSUMABLES => 'قطعات-مصرفی',
            self::CHASSIS_BODY => 'شاسی-و-بدنه',
            self::GEARBOX => 'سیستم-گیربکس-و-دیفرانسیل',
            self::FUEL_SYSTEM => 'سیستم-سوخت‌رسانی',
            self::BRAKE_SUSPENSION => 'سیستم-چرخ-و-ترمز',
            self::ELECTRICAL => 'سیستم-برق',
            self::COOLING => 'سیستم-خنک-کننده',
            self::EXHAUST => 'اگزوز',
            self::INTERIOR => 'تزئینات',
            self::OTHER => 'سایر',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ENGINE => 'fa-cogs',
            self::CONSUMABLES => 'fa-oil-can',
            self::CHASSIS_BODY => 'fa-car',
            self::GEARBOX => 'fa-cog',
            self::FUEL_SYSTEM => 'fa-gas-pump',
            self::BRAKE_SUSPENSION => 'fa-compact-disc',
            self::ELECTRICAL => 'fa-bolt',
            self::COOLING => 'fa-fan',
            self::EXHAUST => 'fa-wind',
            self::INTERIOR => 'fa-chair',
            self::OTHER => 'fa-puzzle-piece',
        };
    }

    public static function list(): array
    {
        return array_map(fn ($case) => [
            'id' => $case->value,
            'label' => $case->label(),
            'slug' => $case->slug(),
        ], self::cases());
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        return null;
    }
}
PHP;

$command = <<<'PHP'
<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CategorizeProducts extends Command
{
    protected $signature = 'products:categorize';
    protected $description = 'Categorize products based on Persian part title keywords';

    private array $rules = [
        6 => ['ترمز', 'لنت', 'کالیپر', 'کاسه ترمز', 'دیسک ترمز', 'کفشک', 'جلوبند', 'سیبک', 'طبق', 'کمک فنر', 'سرکمک', 'توپی', 'بازویی', 'میل موج', 'بوش عقب', 'بوش جلو', 'بوش بازو', 'فنر لول', 'فنر معلق', 'میل تعادل', 'قرقری', 'پمپ ترمز', 'سیلندر ترمز', 'لقمه ترمز', 'بلبرینگ چرخ', 'رینگ abs', 'گاید پین', 'سر اکسل', 'پلوس', 'گردگیر پلوس'],
        1 => ['سیلندر', 'سرسیلندر', 'سر سیلندر', 'پیستون', 'سوپاپ', 'تایپت', 'میل لنگ', 'میل بادامک', 'کارتر', 'روغن موتور', 'بلوک سیلندر', 'بوش سیلندر', 'رینگ موتور', 'یاتاقان', 'شاتون', 'فلایویل', 'واشر سرسیلندر', 'سگمنت', 'دریچه هوا', 'منیفولد', 'اویل پمپ', 'پمپ روغن', 'گیت موتور', 'کارتر روغن'],
        9 => ['اگزوز', 'کاتالیست', 'منبع اگزوز', 'لوله اگزوز', 'حصیری اگزوز', 'flexible'],
        8 => ['رادیاتور', 'واترپمپ', 'واتر پمپ', 'پمپ آب', 'فن رادیاتور', 'ترموستات', 'شیلنگ آب', 'خنک کن', 'منبع آب', 'مخزن آب', 'فن خنک', 'بخاری', 'کولر'],
        4 => ['گیربکس', 'دیفرانسیل', 'کلاچ', 'سنکرون', 'دنده', 'هوزینگ', 'صفحه کلاچ', 'دیسک کلاچ', 'بلبرینگ کلاچ', 'سیم کلاچ', 'لنت کلاچ'],
        5 => ['سوخت', 'انژکتور', 'پمپ بنزین', 'ریل سوخت', 'باک بنزین', 'شمع', 'کوئل', 'دلکو', 'وایرشمع', 'وایر شمع', 'لوله بنزین'],
        7 => ['سنسور', 'کلید', 'چراغ', 'لامپ', 'استارت', 'دینام', 'فیوز', 'ریموت', 'ایموبیلایزر', 'رله', 'بلندگو', 'آژیر', 'بوق', 'سیم کشی', 'دسته سیم', 'ecu', 'واحد کنترل', 'کیسه هوا', 'ایربگ', 'مه شکن', 'راهنما', 'آلترناتور', 'باتری', 'شیشه بالابر', 'سوئیچ', 'قفل مرکزی', 'موتور شیشه', 'آنتن', 'مقاومت بخاری', 'موتور فن', 'کنترل', 'چشمی', 'شیشه شو'],
        2 => ['فیلتر', 'تسمه', 'روغن', 'برف پاک', 'تیغه برف', 'واشر', 'اورینگ', 'کاسه نمد', 'نمد'],
        3 => ['گلگیر', 'سپر', 'درب', 'شیشه', 'آینه', 'بدنه', 'گل پخش', 'زه', 'نوار', 'آرم', 'ستون', 'لولا', 'قاب', 'صندوق', 'کاپوت', 'رکاب', 'شبکه', 'جلو پنجره', 'سقف', 'قفل', 'یراق', 'دستگیره', 'لاستیک درب', 'لاستیک شیشه'],
        10 => ['صندلی', 'کنسول', 'آفتابگیر', 'طاقچه', 'داشبورد', 'پدال', 'فرمان', 'آیینه داخلی', 'کمربند', 'جاکارتی', 'جا کارتی', 'روکش', 'تزئینات'],
    ];

    public function handle()
    {
        $orphaned = DB::table('product_in_category')
            ->whereNotIn('product_id', Product::pluck('id'))
            ->delete();
        $this->info("Deleted {$orphaned} orphaned records.");

        DB::table('product_in_category')->whereBetween('category_id', [1, 11])->delete();

        $products = Product::select(['id', 'title'])->get();
        $assigned = 0;
        $other = 0;
        $rows = [];
        $now = now();

        foreach ($products as $product) {
            $categoryId = $this->detectCategory($product->title);
            $rows[] = [
                'product_id' => $product->id,
                'category_id' => $categoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $categoryId === 11 ? $other++ : $assigned++;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('product_in_category')->insert($chunk);
        }

        $this->info("Categorized {$assigned} products. {$other} assigned to OTHER.");

        $stats = DB::table('product_in_category')
            ->whereBetween('category_id', [1, 11])
            ->select('category_id', DB::raw('count(*) as cnt'))
            ->groupBy('category_id')
            ->orderBy('category_id')
            ->get();

        $this->table(['Category ID', 'Count'], $stats->map(fn ($s) => [$s->category_id, $s->cnt]));
    }

    private function detectCategory(string $title): int
    {
        $title = mb_strtolower(strtr($title, [
            'ي' => 'ی',
            'ك' => 'ک',
            '‌' => ' ',
        ]));

        foreach ($this->rules as $categoryId => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($title, mb_strtolower($keyword)) !== false) {
                    return $categoryId;
                }
            }
        }

        return 11;
    }
}
PHP;

file_put_contents('app/Enums/ProductCategory.php', $enum);
file_put_contents('app/Console/Commands/CategorizeProducts.php', $command);
echo "rewritten category enum and command\n";
