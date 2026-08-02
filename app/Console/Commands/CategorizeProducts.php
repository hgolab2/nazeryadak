<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CategorizeProducts extends Command
{
    protected $signature = 'products:categorize';
    protected $description = 'Categorize products based on title keywords';

    private array $rules = [
        6 => [ // BRAKE_SUSPENSION - ترمز، چرخ، جلوبندی
            'ترمز', 'لنت', 'كاليپر', 'كاسه ترمز', 'ديسك ترمز',
            'كفشك', 'جلوبند', 'سيبك', 'طبق', 'كمك فنر', 'سركمك',
            'سر كمك', 'توپي', 'بازويي', 'ميل موج', 'بوش عقب',
            'بوش جلو', 'بوش بازو', 'فنر لول', 'فنر معلق',
            'ميل تعادل', 'قرقري', 'پمپ ترمز', 'سيلندر ترمز',
            'لقمه ترمز', 'بلبرينگ چرخ', 'رينگ ABS', 'گايد پين',
            'سراكسل', 'سر اكسل', 'پلوس', 'گردگير پلوس',
        ],
        1 => [ // ENGINE - موتور
            'سيلندر', 'سرسيلندر', 'سر سيلندر', 'پيستون', 'سوپاپ',
            'تايپت', 'ميل لنگ', 'ميل بادامك', 'كارتر', 'روغن موتور',
            'بلوك سيلندر', 'بوش سيلندر', 'رينگ موتور', 'ياتاقان',
            'شاتون', 'فلايويل', 'واشر سرسيلندر', 'سگمان',
            'دريچه هوا', 'منيفولد', 'سوپاپ', 'اويل پمپ',
            'پمپ روغن', 'گيت موتور', 'كارتر روغن',
        ],
        9 => [ // EXHAUST - اگزوز
            'اگزوز', 'كاتاليست', 'منبع اگزوز', 'لوله اگزوز',
            'flexible', 'حصيري اگزوز',
        ],
        8 => [ // COOLING - خنک‌کننده
            'رادياتور', 'واترپمپ', 'واتر پمپ', 'پمپ آب', 'فن رادياتور',
            'ترموستات', 'شيلنگ آب', 'خنك كن', 'منبع آب',
            'مخزن آب', 'فن خنك', 'بخاري', 'كولر',
        ],
        4 => [ // GEARBOX - گیربکس
            'گيربكس', 'ديفرانسيل', 'كلاچ', 'سنكرون', 'دنده',
            'هوزينگ', 'صفحه كلاچ', 'ديسك كلاچ', 'بلبرينگ كلاچ',
            'سيم كلاچ', 'لنت كلاچ',
        ],
        5 => [ // FUEL_SYSTEM - سوخت
            'سوخت', 'انژكتور', 'پمپ بنزين', 'ريل سوخت',
            'باك بنزين', 'شمع', 'كوئل', 'دلكو', 'وايرشمع',
            'واير شمع', 'لوله بنزين',
        ],
        7 => [ // ELECTRICAL - برق
            'سنسور', 'كليد', 'چراغ', 'لامپ', 'استارت', 'دينام',
            'فيوز', 'ريموت', 'ايموبيلايزر', 'رله', 'بلندگو',
            'آژير', 'بوق', 'سيم كشي', 'دسته سيم', 'ECU',
            'واحد كنترل', 'كيسه هوا', 'ايربگ', 'مه شكن',
            'راهنما', 'آلترناتور', 'باتري', 'شيشه بالابر',
            'سوئيچ', 'قفل مركزي', 'موتور شيشه',
            'آنتن', 'رادياتور بخاري', 'مقاومت بخاري',
            'موتور فن', 'كنترل', 'چشمي', 'شيشه شو',
        ],
        2 => [ // CONSUMABLES - مصرفی
            'فيلتر', 'تسمه', 'روغن', 'برف پاك', 'تيغه برف',
            'واشر', 'اورينگ', 'كاسه نمد',
        ],
        3 => [ // CHASSIS_BODY - شاسی و بدنه
            'گلگير', 'سپر', 'درب', 'شيشه', 'آينه', 'بدنه',
            'گل پخش', 'زه', 'نوار', 'آرم', 'ستون', 'لولا',
            'قاب', 'صندوق', 'كاپوت', 'ركاب', 'شبكه',
            'جلو پنجره', 'سقف', 'قفل', 'يراق', 'دستگيره',
            'لاستيك درب', 'لاستيك شيشه',
        ],
        10 => [ // INTERIOR - تزئینات
            'صندلي', 'كنسول', 'آفتابگير', 'طاقچه',
            'داشبورد', 'پدال', 'فرمان', 'آيينه داخلي',
            'كمربند', 'جاكارتي', 'جا كارتي', 'روكش',
        ],
    ];

    public function handle()
    {
        $orphaned = DB::table('product_in_category')
            ->whereNotIn('product_id', Product::pluck('id'))
            ->delete();
        $this->info("Deleted {$orphaned} orphaned records.");

        $products = Product::all();
        $assigned = 0;
        $other = 0;
        $rows = [];

        foreach ($products as $product) {
            $title = $product->title;
            $categoryId = $this->detectCategory($title);

            $rows[] = [
                'product_id'  => $product->id,
                'category_id' => $categoryId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];

            if ($categoryId === 11) {
                $other++;
            } else {
                $assigned++;
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('product_in_category')->insert($chunk);
        }

        $this->info("Categorized {$assigned} products. {$other} assigned to 'OTHER'.");

        $stats = DB::table('product_in_category')
            ->select('category_id', DB::raw('count(*) as cnt'))
            ->groupBy('category_id')
            ->orderBy('category_id')
            ->get();

        $this->table(['Category ID', 'Count'], $stats->map(fn ($s) => [$s->category_id, $s->cnt]));
    }

    private function detectCategory(string $title): int
    {
        foreach ($this->rules as $categoryId => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($title, $keyword) !== false) {
                    return $categoryId;
                }
            }
        }
        return 11; // OTHER
    }
}
