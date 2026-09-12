<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

/**
 * سود و زیان فروشگاه در یک بازه.
 *
 * یک منبع برای سه مصرف‌کننده: داشبورد پنل، صفحه‌ی حسابداری و ربات بله.
 * هر جا عدد «سود خالص» نشان داده می‌شود باید از همین کلاس آمده باشد تا سه
 * جا سه عدد مختلف نگویند.
 *
 * تعریف‌ها:
 *   فروش        = مبلغ پرداختی سفارش‌های تسویه‌شده (اقلام − تخفیف + ارسال)
 *   بهای کالا   = جمع قیمت خرید اقلام همان سفارش‌ها (order_items.unit_cost)
 *   هزینه‌ی سفارش = تراکنش‌های هزینه‌ای که order_id دارند (پست، بسته‌بندی)
 *   هزینه‌ی عمومی = تراکنش‌های هزینه‌ای بدون سفارش (سرور، پیامک، تبلیغات)
 *   سایر درآمد   = تراکنش‌های درآمدی (فروش خارج از سایت و ...)
 *   سود خالص    = فروش + سایر درآمد − بهای کالا − هزینه‌ی سفارش − هزینه‌ی عمومی
 *
 * تاریخ سفارش برای گزارش، لحظه‌ی تسویه (paid_at) است؛ سفارشی که مهر ثبت
 * شده و آبان پرداخت شده، درآمد آبان است.
 */
class FinanceReport
{
    /**
     * @return array{
     *   from: Carbon, to: Carbon,
     *   orders: int, items: int, sales: int, cogs: int,
     *   order_expenses: int, general_expenses: int, other_income: int,
     *   gross_profit: int, net_profit: int, margin: float,
     *   expenses_by_category: array<string,int>
     * }
     */
    public static function period(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->endOfDay();

        $orders = Order::query()
            ->whereIn('status', Order::SETTLED_STATUSES)
            ->whereBetween(DB::raw('COALESCE(paid_at, created_at)'), [$from, $to]);

        $orderIds = (clone $orders)->pluck('id');

        $sales = (int) (clone $orders)->sum('total_price');
        $count = $orderIds->count();

        $itemsRow = DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->selectRaw('COALESCE(SUM(quantity), 0) AS qty, COALESCE(SUM(COALESCE(unit_cost, 0) * quantity), 0) AS cost')
            ->first();

        $items = (int) ($itemsRow->qty ?? 0);
        $cogs  = (int) ($itemsRow->cost ?? 0);

        // هزینه‌های سفارش بر اساس تاریخ خودشان شمرده می‌شوند نه تاریخ سفارش؛
        // هزینه‌ی پستِ سفارشِ آخر ماه که اول ماه بعد پرداخت شده، مال ماه بعد است
        $expenses = fn () => FinanceTransaction::expenses()->between($from->toDateString(), $to->toDateString());

        $orderExpenses   = (int) $expenses()->whereNotNull('order_id')->sum('amount');
        $generalExpenses = (int) $expenses()->whereNull('order_id')->sum('amount');

        $byCategory = $expenses()
            ->selectRaw('category, SUM(amount) AS total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category')
            ->map(fn ($total) => (int) $total)
            ->all();

        $otherIncome = (int) FinanceTransaction::incomes()
            ->between($from->toDateString(), $to->toDateString())
            ->sum('amount');

        $gross = $sales - $cogs;
        $net   = $gross + $otherIncome - $orderExpenses - $generalExpenses;

        return [
            'from'                 => $from,
            'to'                   => $to,
            'orders'               => $count,
            'items'                => $items,
            'sales'                => $sales,
            'cogs'                 => $cogs,
            'order_expenses'       => $orderExpenses,
            'general_expenses'     => $generalExpenses,
            'other_income'         => $otherIncome,
            'gross_profit'         => $gross,
            'net_profit'           => $net,
            'margin'               => $sales > 0 ? round($net * 100 / $sales, 1) : 0.0,
            'expenses_by_category' => $byCategory,
        ];
    }

    /** امروز (به وقت تهران). */
    public static function today(): array
    {
        return self::period(now(), now());
    }

    /** هفت روز اخیر شامل امروز. */
    public static function lastDays(int $days = 7): array
    {
        return self::period(now()->subDays(max(0, $days - 1)), now());
    }

    /** ماه شمسی جاری. */
    public static function thisMonth(): array
    {
        [$from, $to] = self::jalaliMonthRange(Jalalian::now()->getYear(), Jalalian::now()->getMonth());

        return self::period($from, $to);
    }

    /** یک ماه شمسی مشخص. */
    public static function jalaliMonth(int $year, int $month): array
    {
        [$from, $to] = self::jalaliMonthRange($year, $month);

        return self::period($from, $to);
    }

    /** از ابتدا تا امروز. */
    public static function allTime(): array
    {
        $first = Order::min('created_at');

        return self::period($first ? Carbon::parse($first) : now()->subYears(5), now());
    }

    /**
     * بازه‌ی میلادی یک ماه شمسی.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function jalaliMonthRange(int $year, int $month): array
    {
        $start = (new Jalalian($year, $month, 1))->toCarbon();
        $end   = $start->copy()->addDays((new Jalalian($year, $month, 1))->getMonthDays() - 1);

        return [$start, $end];
    }

    /**
     * فروش و سود ماه‌به‌ماه شمسی برای نمودار/جدول روند؛ جدیدترین ماه اول.
     *
     * @return array<int, array{label: string, year: int, month: int, report: array}>
     */
    public static function monthlyTrend(int $months = 6): array
    {
        $rows  = [];
        $point = Jalalian::now();

        for ($i = 0; $i < $months; $i++) {
            $year  = $point->getYear();
            $month = $point->getMonth();

            $rows[] = [
                'label'  => $point->format('%B %Y'),
                'year'   => $year,
                'month'  => $month,
                'report' => self::jalaliMonth($year, $month),
            ];

            $point = $point->subMonths(1);
        }

        return $rows;
    }

    /**
     * شمارنده‌های عمومی فروشگاه که ربطی به بازه ندارند؛ برای «آمار» بله و
     * کارت‌های داشبورد.
     */
    public static function snapshot(): array
    {
        $statusCounts = Order::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'products'         => (int) Product::count(),
            'products_active'  => (int) Product::where('is_active', 1)->count(),
            'products_nostock' => (int) Product::where('stock', '<=', 0)->count(),
            'customers'        => (int) Customer::count(),
            'orders'           => (int) array_sum($statusCounts),
            'orders_open'      => (int) array_sum(array_intersect_key($statusCounts, array_flip(Order::OPEN_STATUSES))),
            'status_counts'    => $statusCounts,
            'pending_receipts' => (int) Payment::awaitingReview()->count(),
        ];
    }

    /**
     * پرفروش‌ترین قطعات یک بازه (بر اساس تعداد).
     *
     * @return array<int, array{title: string, qty: int, sales: int}>
     */
    public static function topProducts(Carbon $from, Carbon $to, int $limit = 5): array
    {
        return DB::table('order_items AS oi')
            ->join('orders AS o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('products AS p', 'p.id', '=', 'oi.product_id')
            ->whereIn('o.status', Order::SETTLED_STATUSES)
            ->whereBetween(DB::raw('COALESCE(o.paid_at, o.created_at)'), [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('oi.product_id', 'p.title')
            ->orderByDesc('qty')
            ->limit($limit)
            ->selectRaw('oi.product_id, p.title, SUM(oi.quantity) AS qty, SUM(oi.total_price) AS sales')
            ->get()
            // محصول حذف‌شده عنوان ندارد؛ شماره‌اش نشان داده می‌شود
            ->map(fn ($row) => [
                'title' => $row->title !== null && $row->title !== '' ? (string) $row->title : 'محصول #' . $row->product_id,
                'qty'   => (int) $row->qty,
                'sales' => (int) $row->sales,
            ])
            ->all();
    }
}
