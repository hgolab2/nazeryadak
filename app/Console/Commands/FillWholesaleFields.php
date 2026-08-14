<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * پرکردن ستون‌های عمده‌فروشی محصولات با عدد صریح.
 *
 * تا وقتی ستون‌ها خالی‌اند، تعداد و قیمت عمده در لحظه‌ی نمایش حساب می‌شوند و
 * مدیر عددی برای دیدن و ویرایش ندارد. این دستور همان محاسبه را یک‌بار انجام
 * می‌دهد و نتیجه را می‌نویسد.
 *
 * چرا سقف؟ تعداد خودکار از تقسیم مبلغ ارسال رایگان بر قیمت به‌دست می‌آید، پس
 * برای قطعه‌ی ارزان عددهای غیرواقعی (چند ده‌هزار عدد) می‌دهد. با --max آن را
 * به عددی می‌بریم که واقعا سفارش‌دادنی است. در عوض چنین سفارشی دیگر لزوما به
 * مبلغ ارسال رایگان نمی‌رسد؛ صفحه‌ی محصول این را از
 * Product::wholesaleReachesFreeShipping() می‌فهمد و وعده‌ی ارسال رایگان را
 * فقط جایی نشان می‌دهد که برقرار باشد.
 *
 * اجرای دوباره بی‌خطر است: مقدارها هر بار از نو حساب می‌شوند، نه از روی
 * چیزی که دفعه‌ی قبل نوشته شده.
 */
class FillWholesaleFields extends Command
{
    protected $signature = 'wholesale:fill
        {--min=6 : کمترین تعداد آستانه}
        {--max=24 : بیشترین تعداد آستانه؛ صفر یعنی بدون سقف}
        {--only-empty : فقط محصولاتی که ستونشان خالی است پر شوند}
        {--reset : به‌جای پرکردن، هر دو ستون خالی (NULL) شوند تا دوباره خودکار حساب شوند}
        {--dry-run : فقط گزارش بدهد و چیزی ننویسد}';

    protected $description = 'نوشتن تعداد و قیمت عمده‌ی هر محصول در ستون‌های wholesale_min_qty و wholesale_price';

    public function handle(): int
    {
        if ($this->option('reset')) {
            return $this->reset();
        }

        $min = max(1, (int) $this->option('min'));
        $max = (int) $this->option('max');
        if ($max > 0 && $max < $min) {
            $this->error('مقدار --max نمی‌تواند از --min کمتر باشد.');
            return self::FAILURE;
        }

        $dry       = (bool) $this->option('dry-run');
        $onlyEmpty = (bool) $this->option('only-empty');
        $target    = wholesaleTargetAmount();

        $query = Product::query()->select(['id', 'price', 'compare_at_price', 'stock', 'category_id']);
        if ($onlyEmpty) {
            $query->whereNull('wholesale_min_qty');
        }

        $written = 0;
        $skipped = 0;
        $capped  = 0;
        $freeShipping = 0;
        $testable = 0;

        $query->chunkById(500, function ($products) use (
            $min, $max, $dry, $target, &$written, &$skipped, &$capped, &$freeShipping, &$testable
        ) {
            foreach ($products as $product) {
                // قطعه‌ی استعلامی قیمت ندارد، پس عمده هم برایش بی‌معناست
                if ((int) $product->price <= 0 || $product->isContactPrice()) {
                    $skipped++;
                    continue;
                }

                $auto  = $product->autoWholesaleMinQty();
                $qty   = max($min, $auto);
                if ($max > 0 && $qty > $max) {
                    $qty = $max;
                    $capped++;
                }

                $price = $product->autoWholesalePrice();
                if ($price <= 0 || $price >= (int) $product->price) {
                    $skipped++;
                    continue;
                }

                if ($qty * $price >= $target) {
                    $freeShipping++;
                }
                if ((int) $product->stock >= $qty) {
                    $testable++;
                }

                if (! $dry) {
                    DB::table('products')->where('id', $product->id)->update([
                        'wholesale_min_qty' => $qty,
                        'wholesale_price'   => $price,
                    ]);
                }

                $written++;
            }
        });

        $this->info(($dry ? '[آزمایشی] ' : '') . 'محصولات پرشده: ' . $written);
        $this->line('  رد شده (بدون قیمت یا استعلامی): ' . $skipped);
        $this->line('  رسیده به سقف ' . ($max ?: '∞') . ' عدد: ' . $capped);
        $this->line('  خریدشان به مبلغ ارسال رایگان می‌رسد: ' . $freeShipping);
        $this->line('  موجودی انبارشان به آستانه می‌رسد (قابل تست): ' . $testable);

        return self::SUCCESS;
    }

    private function reset(): int
    {
        if ($this->option('dry-run')) {
            $count = Product::query()->whereNotNull('wholesale_min_qty')->count();
            $this->info('[آزمایشی] خالی می‌شد: ' . $count . ' محصول');
            return self::SUCCESS;
        }

        $count = DB::table('products')->update([
            'wholesale_min_qty' => null,
            'wholesale_price'   => null,
        ]);

        $this->info('ستون‌های عمده خالی شد: ' . $count . ' محصول (دوباره خودکار حساب می‌شوند)');

        return self::SUCCESS;
    }
}
