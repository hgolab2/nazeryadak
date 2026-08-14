<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * مدیریت کدهای تخفیف.
 *
 * قاعده‌ی هر کد یک جمله است: «X درصد تخفیف تا سقف Y تومان»، به‌علاوه‌ی
 * چند محدودیت اختیاری (حداقل مبلغ فاکتور، بازه‌ی زمانی، سقف دفعات).
 */
class DiscountAdminController extends Controller
{
    private const PER_PAGE = 30;

    private function guard()
    {
        if (! Auth::user()) {
            return redirect('/loginAdmin');
        }
        // همان کد دسترسی سفارشات؛ کد تخفیف بخشی از همان پرونده‌ی فروش است
        access(388);

        return null;
    }

    public function index(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $query = DiscountCode::query();

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($request->input('state') === 'active') {
            $query->where('is_active', 1);
        } elseif ($request->input('state') === 'inactive') {
            $query->where('is_active', 0);
        }

        $model = $query->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString();

        return view('discount.admin.list', compact('model'));
    }

    public function create()
    {
        if ($response = $this->guard()) {
            return $response;
        }

        return view('discount.admin.form', ['model' => null]);
    }

    public function edit($id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        return view('discount.admin.form', ['model' => DiscountCode::findOrFail($id)]);
    }

    public function store(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $data = $this->validated($request);

        DiscountCode::create($data);

        return redirect('/admin/discount/list')->with('success', 'کد تخفیف ثبت شد.');
    }

    public function update(Request $request, $id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $code = DiscountCode::findOrFail($id);
        $data = $this->validated($request, $code->id);

        $code->update($data);

        return redirect('/admin/discount/list')->with('success', 'کد تخفیف به‌روزرسانی شد.');
    }

    /** روشن/خاموش کردن سریع از خود فهرست. */
    public function toggle($id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $code = DiscountCode::findOrFail($id);
        $code->update(['is_active' => ! $code->is_active]);

        return redirect('/admin/discount/list')
            ->with('success', 'کد ' . $code->code . ' ' . ($code->is_active ? 'فعال' : 'غیرفعال') . ' شد.');
    }

    public function destroy($id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $code = DiscountCode::findOrFail($id);

        // سفارش‌های گذشته اسنپ‌شات کد و مبلغ تخفیف را در خود دارند، ولی
        // حذف کدی که هنوز روی سفارش‌های ثبت‌شده نشسته، رد پای فروش را
        // مبهم می‌کند. غیرفعال‌کردن همان کار را بدون خرابی انجام می‌دهد.
        if ($code->usedCount() > 0) {
            return redirect('/admin/discount/list')->withErrors([
                'code' => 'این کد در سفارش‌های ثبت‌شده به کار رفته و حذف نمی‌شود؛ به‌جای حذف، غیرفعالش کنید.',
            ]);
        }

        $code->delete();

        return redirect('/admin/discount/list')->with('success', 'کد تخفیف حذف شد.');
    }

    /**
     * اعتبارسنجی مشترک ثبت و ویرایش.
     *
     * مدیر ممکن است مبلغ‌ها را با ارقام فارسی یا با جداکننده‌ی هزارگان
     * بنویسد؛ پیش از اعتبارسنجی به عدد خام تبدیل می‌شوند وگرنه قاعده‌ی
     * integer ردشان می‌کند.
     */
    private function validated(Request $request, $ignoreId = null): array
    {
        $number = function ($value) {
            $value = preg_replace('/[^\d]/', '', toLatinDigits($value));

            return $value === '' ? null : (int) $value;
        };

        $request->merge([
            'code'               => DiscountCode::normalizeCode($request->input('code')),
            'percent'            => $number($request->input('percent')),
            'max_discount'       => $number($request->input('max_discount')),
            'min_order_amount'   => $number($request->input('min_order_amount')) ?? 0,
            'usage_limit'        => $number($request->input('usage_limit')),
            'per_customer_limit' => $number($request->input('per_customer_limit')),
        ]);

        $data = $request->validate([
            'code'               => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_\-]+$/',
                                     Rule::unique('discount_codes', 'code')->ignore($ignoreId)],
            'title'              => 'nullable|string|max:150',
            'percent'            => 'required|integer|min:1|max:100',
            'max_discount'       => 'nullable|integer|min:0',
            'min_order_amount'   => 'nullable|integer|min:0',
            'usage_limit'        => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'starts_at'          => 'nullable|date',
            'expires_at'         => 'nullable|date|after:starts_at',
        ], [], [
            'code'               => 'کد تخفیف',
            'title'              => 'عنوان',
            'percent'            => 'درصد تخفیف',
            'max_discount'       => 'سقف تخفیف',
            'min_order_amount'   => 'حداقل مبلغ سفارش',
            'usage_limit'        => 'سقف کل دفعات استفاده',
            'per_customer_limit' => 'سقف استفاده هر مشتری',
            'starts_at'          => 'شروع اعتبار',
            'expires_at'         => 'پایان اعتبار',
        ]);

        // سقف صفر یعنی «بی‌سقف»؛ نگه‌داشتنش به صورت صفر باعث می‌شد تخفیف
        // همیشه صفر شود و کد بی‌سروصدا بی‌اثر بماند.
        $data['max_discount']     = empty($data['max_discount']) ? null : (int) $data['max_discount'];
        $data['min_order_amount'] = (int) ($data['min_order_amount'] ?? 0);
        $data['is_active']        = $request->boolean('is_active');

        return $data;
    }
}
