<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Services\FinanceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;

/**
 * حسابداری فروشگاه: دفتر درآمد/هزینه و گزارش سود ماهانه.
 *
 * فروش سفارش‌ها خودکار از جدول orders می‌آید؛ این‌جا فقط چیزهایی ثبت
 * می‌شود که سیستم خودش نمی‌داند (پول سرور، پیامک، هزینه‌ی پست یک سفارش،
 * درآمد بیرون از سایت). سود خالص = فروش − قیمت خرید − همه‌ی این هزینه‌ها.
 */
class FinanceAdminController extends Controller
{
    /** صفحه‌ی اصلی: خلاصه‌ی ماه انتخابی + فهرست تراکنش‌ها + روند ۶ ماهه */
    public function index(Request $request)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        [$year, $month] = $this->selectedMonth($request);
        [$from, $to]    = FinanceReport::jalaliMonthRange($year, $month);

        $query = FinanceTransaction::with(['order', 'creator'])
            ->between($from->toDateString(), $to->toDateString());

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $transactions = $query->orderByDesc('occurred_on')->orderByDesc('id')->paginate(50)->withQueryString();

        return view('finance.admin.index', [
            'year'         => $year,
            'month'        => $month,
            'monthLabel'   => (new Jalalian($year, $month, 1))->format('%B %Y'),
            'report'       => FinanceReport::jalaliMonth($year, $month),
            'trend'        => FinanceReport::monthlyTrend(6),
            'allTime'      => FinanceReport::allTime(),
            'transactions' => $transactions,
            'monthOptions' => $this->monthOptions(),
            'categories'   => FinanceTransaction::CATEGORIES,
            'topProducts'  => FinanceReport::topProducts($from, $to, 5),
        ]);
    }

    public function create(Request $request)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        return view('finance.admin.form', [
            'model'      => null,
            'order'      => $request->filled('order_id') ? Order::find((int) $request->input('order_id')) : null,
            'categories' => FinanceTransaction::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $data = $this->validated($request);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $data['created_by'] = Auth::id();
        $transaction = FinanceTransaction::create($data);

        // از صفحه‌ی سفارش آمده؟ به همان‌جا برگرد
        if ($transaction->order_id && $request->input('return') === 'order') {
            return redirect('/admin/order/show/' . $transaction->order_id)
                ->with('success', 'هزینه‌ی «' . $transaction->title . '» برای سفارش ثبت شد.');
        }

        return redirect($this->indexUrl($transaction))->with('success', 'تراکنش ثبت شد.');
    }

    public function edit($id)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $model = FinanceTransaction::findOrFail($id);

        return view('finance.admin.form', [
            'model'      => $model,
            'order'      => $model->order,
            'categories' => FinanceTransaction::CATEGORIES,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $transaction = FinanceTransaction::findOrFail($id);
        $data        = $this->validated($request);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $transaction->update($data);

        return redirect($this->indexUrl($transaction))->with('success', 'تراکنش ویرایش شد.');
    }

    public function destroy($id)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $transaction = FinanceTransaction::findOrFail($id);
        $transaction->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'تراکنش حذف شد.');
    }

    /**
     * اعتبارسنجی فرم؛ تاریخ شمسی به میلادی تبدیل می‌شود.
     *
     * @return array|\Illuminate\Http\RedirectResponse
     */
    private function validated(Request $request)
    {
        $input = $request->all();
        $input['amount']      = (int) str_replace(',', '', toLatinDigits((string) ($input['amount'] ?? '')));
        $input['occurred_on'] = $this->toGregorian((string) ($input['occurred_on'] ?? ''));
        $input['order_id']    = ($input['order_id'] ?? '') !== '' ? (int) toLatinDigits((string) $input['order_id']) : null;

        $type       = (string) ($input['type'] ?? '');
        $categories = array_keys(FinanceTransaction::CATEGORIES[$type] ?? []);

        $validator = Validator::make($input, [
            'type'        => 'required|in:' . implode(',', array_keys(FinanceTransaction::TYPES)),
            'category'    => 'required|in:' . implode(',', $categories ?: ['-']),
            'title'       => 'required|string|max:190',
            'amount'      => 'required|integer|min:1',
            'order_id'    => 'nullable|integer|exists:orders,id',
            'occurred_on' => 'required|date',
            'note'        => 'nullable|string|max:2000',
        ], [
            'type.required'        => 'نوع تراکنش را انتخاب کنید.',
            'type.in'              => 'نوع تراکنش معتبر نیست.',
            'category.required'    => 'دسته را انتخاب کنید.',
            'category.in'          => 'دسته با نوع تراکنش هم‌خوانی ندارد.',
            'title.required'       => 'عنوان را بنویسید.',
            'amount.required'      => 'مبلغ را وارد کنید.',
            'amount.integer'       => 'مبلغ باید عدد باشد.',
            'amount.min'           => 'مبلغ باید بزرگ‌تر از صفر باشد.',
            'order_id.exists'      => 'سفارشی با این شماره وجود ندارد.',
            'occurred_on.required' => 'تاریخ را وارد کنید.',
            'occurred_on.date'     => 'تاریخ معتبر نیست (مثال: ۱۴۰۵/۰۶/۲۰).',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        return [
            'type'        => $type,
            'category'    => $input['category'],
            'title'       => trim((string) $input['title']),
            'amount'      => $input['amount'],
            'order_id'    => $input['order_id'],
            'occurred_on' => $input['occurred_on'],
            'note'        => trim((string) ($input['note'] ?? '')) ?: null,
        ];
    }

    /** «۱۴۰۵/۰۶/۲۰» → «2026-09-11»؛ ورودی میلادی دست‌نخورده می‌ماند. */
    private function toGregorian(string $value): string
    {
        $value = trim(toLatinDigits($value));

        if ($value === '') {
            return '';
        }

        if (preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/', $value, $m) && (int) $m[1] < 1700) {
            try {
                [$y, $mo, $d] = CalendarUtils::toGregorian((int) $m[1], (int) $m[2], (int) $m[3]);

                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            } catch (\Throwable $e) {
                return '';
            }
        }

        return $value;
    }

    /** ماه انتخاب‌شده در فیلتر، یا ماه جاری */
    private function selectedMonth(Request $request): array
    {
        $now   = Jalalian::now();
        $year  = (int) toLatinDigits((string) $request->input('year', $now->getYear()));
        $month = (int) toLatinDigits((string) $request->input('month', $now->getMonth()));

        if ($year < 1390 || $year > 1500) {
            $year = $now->getYear();
        }

        if ($month < 1 || $month > 12) {
            $month = $now->getMonth();
        }

        return [$year, $month];
    }

    /** ۲۴ ماه اخیر برای فیلتر */
    private function monthOptions(): array
    {
        $options = [];
        $point   = Jalalian::now();

        for ($i = 0; $i < 24; $i++) {
            $options[] = ['year' => $point->getYear(), 'month' => $point->getMonth(), 'label' => $point->format('%B %Y')];
            $point = $point->subMonths(1);
        }

        return $options;
    }

    private function indexUrl(FinanceTransaction $transaction): string
    {
        $date = Jalalian::fromCarbon($transaction->occurred_on);

        return '/admin/finance?year=' . $date->getYear() . '&month=' . $date->getMonth();
    }
}
