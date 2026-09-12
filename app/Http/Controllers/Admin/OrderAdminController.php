<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\OrderItem;
use App\Services\OrderStatusService;
use App\Support\Mobile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderAdminController extends Controller
{
    /** لیست سفارشات */
    public function admin_list(Request $request)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        // pendingReceipt هم eager می‌شود تا مدیر در همان لیست ببیند کدام سفارش
        // رسید بررسی‌نشده دارد
        $query = Order::with(['customer', 'items', 'pendingReceipt', 'expenses']);

        // فیلترها — کادرهای جستجو با ارقام فارسی هم پر می‌شوند و بدون تبدیل،
        // جستجو همیشه «موردی یافت نشد» می‌داد
        if ($request->filled('order_id')) {
            $query->where('id', (int) toLatinDigits($request->input('order_id')));
        }

        if ($request->filled('phone')) {
            $phone = toLatinDigits($request->input('phone'));
            $query->whereHas('customer', function ($q) use ($phone) {
                $q->where('phone', 'like', "%{$phone}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // نام ستون مستقیم از کوئری‌استرینگ به SQL می‌رفت؛ هر مقدار نامعتبری
        // لیست را با خطای دیتابیس می‌شکست
        $sortable = ['id', 'total_price', 'final_price', 'status', 'created_at'];
        $sort = in_array($request->input('order'), $sortable, true) ? $request->input('order') : 'id';
        $dir  = strtolower((string) $request->input('orderby')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $dir);

        $perPage = (int) $request->input('showcount', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

        // paginate خودش تعداد کل را می‌شمارد؛ count() جداگانه یک کوئری اضافه بود
        $model      = $query->paginate($perPage);
        $totalCount = $model->total();

        if ($request->ajax()) {
            $view = view('order.admin.list_type', compact('model', 'totalCount'))->render();
            return response()->json([
                'html' => $view,
                'totalCount' => $totalCount,
            ]);
        }

        return view('order.admin.list', compact('model', 'totalCount'));
    }

    /** فرم ایجاد */
    public function admin_create()
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        return view('order.admin.create', [
            'customers' => Customer::orderBy('id', 'desc')->get(),
            //'shippingMethods' => ShippingMethod::all(),
            'addresses' => [],
        ]);
    }

    /** ذخیره سفارش */
    public function admin_store(Request $request)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $validator = Validator::make($request->all(), $this->orderRules(), $this->orderMessages());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $order = new Order($request->only([
            'customer_id',
            'address_id',
            'shipping_method_id',
            'final_price',
            'status',
        ]));

        $order->shipping_price = (int) $request->input('shipping_price', 0);
        $order->recalculateTotals();
        $order->save();

        return redirect('/admin/order/list')
            ->with('success', 'سفارش با موفقیت ثبت شد');
    }

    /** فرم ویرایش */
    public function admin_edit($id)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $model = Order::findOrFail($id);

        return view('order.admin.create', [
            'model' => $model,
            'customers' => Customer::orderBy('id', 'desc')->get(),
            //'shippingMethods' => ShippingMethod::all(),
            'addresses' => CustomerAddress::where('customer_id', $model->customer_id)->get(),
        ]);
    }

    /** ویرایش سفارش */
    public function admin_update(Request $request, $id)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $order = Order::findOrFail($id);

        $validator = Validator::make($request->all(), $this->orderRules(), $this->orderMessages());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $order->fill($request->only([
            'customer_id',
            'address_id',
            'shipping_method_id',
            'final_price',
        ]));

        $order->shipping_price = (int) $request->input('shipping_price', 0);
        $order->recalculateTotals();
        $order->save();

        // وضعیت جدا و از مسیر مشترک عوض می‌شود تا موجودی و اطلاع‌رسانی
        // دقیقا مثل بقیه‌ی راه‌های تغییر وضعیت رفتار کند. ویرایش مبلغ یا
        // آدرس به‌تنهایی برای مشتری پیامک نمی‌فرستد.
        (new OrderStatusService())->change($order, (string) $request->input('status'), 'panel');

        return redirect('/admin/order/show/' . $order->id)
            ->with('success', 'سفارش با موفقیت بروزرسانی شد');
    }

    /**
     * برچسب پستی یک سفارش — صفحه‌ای مخصوص چاپ که روی جعبه چسبانده می‌شود.
     */
    public function admin_label(Request $request, $id)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $orders = Order::with(['customer', 'items', 'address.province'])
            ->where('id', $id)
            ->get();

        if ($orders->isEmpty()) {
            abort(404);
        }

        return view('order.admin.label', [
            'orders' => $orders,
            'size'   => $this->labelSize($request),
        ]);
    }

    /**
     * چاپ گروهی برچسب برای سفارش‌های انتخاب‌شده در لیست؛ شناسه‌ها با کاما
     * می‌آیند تا لینک قابل باز کردن در تب جدید باشد.
     */
    public function admin_labels(Request $request)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->take(200)     // یک درخواست نباید کل جدول سفارش‌ها را چاپ کند
            ->values();

        if ($ids->isEmpty()) {
            return redirect('/admin/order/list')
                ->with('error', 'هیچ سفارشی برای چاپ برچسب انتخاب نشده است.');
        }

        $orders = Order::with(['customer', 'items', 'address.province'])
            ->whereIn('id', $ids)
            ->orderBy('id', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            return redirect('/admin/order/list')
                ->with('error', 'سفارشی با شناسه‌های انتخاب‌شده پیدا نشد.');
        }

        return view('order.admin.label', [
            'orders' => $orders,
            'size'   => $this->labelSize($request),
        ]);
    }

    /** اندازه‌ی کاغذ برچسب: رول ۱۰×۱۵ یا چهارتایی روی A4 */
    private function labelSize(Request $request): string
    {
        return $request->query('size') === 'a4' ? 'a4' : '10x15';
    }

    /** حذف سفارش */
    public function admin_destroy($id)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $order = Order::findOrFail($id);

        // جدول‌ها MyISAM هستند و کلید خارجیِ آبشاری ندارند؛ بدون این دو خط،
        // اقلام و پرداخت‌های سفارشِ حذف‌شده برای همیشه یتیم می‌ماندند
        DB::table('order_items')->where('order_id', $order->id)->delete();
        DB::table('payments')->where('order_id', $order->id)->delete();
        // هزینه‌های ثبت‌شده پول واقعی خرج‌شده‌اند؛ پاک نمی‌شوند، فقط از سفارش جدا می‌شوند
        DB::table('finance_transactions')->where('order_id', $order->id)->update(['order_id' => null]);

        $order->delete();

        return response()->json(['success' => true]);
    }

    /**
     * صفحه‌ی مشاهده‌ی سفارش.
     *
     * لینک «مشاهده» در لیست به /admin/order/show/{id} می‌رفت که هیچ مسیری
     * برایش تعریف نشده بود و ۴۰۴ می‌داد؛ یعنی مدیر هیچ صفحه‌ای برای دیدن
     * اقلام، آدرس و پرداخت‌های یک سفارش نداشت.
     */
    public function admin_show($id)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $order = Order::with([
            'customer',
            'address.province',
            'items.product',
            'payments.reviewer',
            'shippingMethod',
            'expenses.creator',
        ])->find($id);

        if (! $order) {
            return redirect('/admin/order/list')
                ->with('error', 'سفارشی با شناسه‌ی ' . (int) $id . ' پیدا نشد.');
        }

        return view('order.admin.show', compact('order'));
    }

    /**
     * تغییر سریع وضعیت از منوی کشویی لیست، داشبورد و صفحه‌ی مشاهده.
     *
     * پاسخ JSON است تا صفحه دوباره بارگذاری نشود؛ مسیر مشترک
     * OrderStatusService موجودی و پیامک/بله را هم انجام می‌دهد.
     */
    public function admin_status(Request $request, $id)
    {
        if (!Auth::user()) return response()->json(['success' => false, 'message' => 'وارد نشده‌اید.'], 401);
        access(388);

        $order  = Order::findOrFail($id);
        $status = (string) $request->input('status');

        if (! isset(Order::STATUSES[$status])) {
            return response()->json(['success' => false, 'message' => 'وضعیت انتخاب‌شده معتبر نیست.'], 422);
        }

        $changed = (new OrderStatusService())->change($order, $status, 'panel');

        return response()->json([
            'success' => true,
            'changed' => $changed,
            'status'  => $status,
            'label'   => Order::STATUSES[$status],
            'badge'   => $order->statusBadgeClass(),
            'message' => $changed
                ? 'وضعیت سفارش #' . $order->id . ' به «' . Order::STATUSES[$status] . '» تغییر کرد.'
                : 'وضعیت سفارش همان بود.',
        ]);
    }

    /**
     * ثبت/اصلاح قیمت خرید یک قلم از صفحه‌ی سفارش.
     *
     * قیمت خرید در لحظه‌ی سفارش قفل می‌شود، ولی گاهی غلط یا خالی است (محصول
     * حذف‌شده، قطعه‌ی استعلامی که بعدا قیمت گرفته). مدیر همین‌جا درستش
     * می‌کند و سود سفارش دوباره حساب می‌شود.
     */
    public function admin_item_cost(Request $request, $id, $itemId)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $item = OrderItem::where('order_id', $id)->findOrFail($itemId);

        $cost = (int) str_replace(',', '', toLatinDigits((string) $request->input('unit_cost', '')));

        if ($cost < 0) {
            return back()->with('error', 'قیمت خرید نمی‌تواند منفی باشد.');
        }

        $item->unit_cost = $cost;
        $item->save();

        // اگر قطعه‌ی استعلامی بوده و مدیر حالا قیمت فروشش را هم می‌داند
        if ($request->filled('unit_price')) {
            $price = (int) str_replace(',', '', toLatinDigits((string) $request->input('unit_price')));

            if ($price >= 0) {
                $item->unit_price  = $price;
                $item->total_price = $price * (int) $item->quantity;
                $item->save();

                $order = Order::findOrFail($id);
                $order->final_price = (int) $order->items()->sum('total_price');
                $order->recalculateTotals();
                $order->save();
            }
        }

        return back()->with('success', 'قیمت قلم ذخیره شد.');
    }

    /**
     * قوانین اعتبارسنجی مشترک ثبت و ویرایش سفارش.
     *
     * total_price اینجا نیست: مبلغ پرداختی از روی اقلام، تخفیف و هزینه‌ی
     * ارسال در Order::recalculateTotals() ساخته می‌شود، نه از فرم.
     */
    private function orderRules(): array
    {
        return [
            'customer_id'        => 'required|exists:customers,id',
            'address_id'         => 'nullable|exists:customer_addresses,id',
            // جدول shipping_methods خالی است و فرم هم چنین فیلدی ندارد؛ با
            // required بودنِ قبلی هیچ سفارشی از پنل ذخیره یا ویرایش نمی‌شد
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'shipping_price'     => 'nullable|integer|min:0',
            'final_price'        => 'required|integer|min:0',
            'status'             => 'required|string|in:' . implode(',', array_keys(Order::STATUSES)),
        ];
    }

    private function orderMessages(): array
    {
        return [
            'customer_id.required'    => 'مشتری سفارش را انتخاب کنید.',
            'customer_id.exists'      => 'مشتری انتخاب‌شده وجود ندارد.',
            'address_id.exists'       => 'آدرس انتخاب‌شده ثبت نشده است.',
            'shipping_price.integer'  => 'هزینه ارسال باید عدد باشد.',
            'shipping_price.min'      => 'هزینه ارسال نمی‌تواند منفی باشد.',
            'final_price.required'    => 'جمع کل اقلام را وارد کنید.',
            'final_price.integer'     => 'جمع کل اقلام باید عدد باشد.',
            'final_price.min'         => 'جمع کل اقلام نمی‌تواند منفی باشد.',
            'status.required'         => 'وضعیت سفارش را انتخاب کنید.',
            'status.in'               => 'وضعیت انتخاب‌شده معتبر نیست.',
        ];
    }

    /** لیست مشتریان */
    public function admin_customer_list(Request $request)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $query = Customer::query();

        // جستجو
        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->name}%")
                ->orWhere('last_name', 'like', "%{$request->name}%");
            });
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', "%{$request->phone}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy(
            $request->order ?? 'id',
            $request->orderby ?? 'desc'
        );

        $totalCount = $query->count();
        $model = $query->paginate($request->showcount ?? 20);

        if ($request->ajax()) {
            $view = view('customer.admin.list_type', compact('model', 'totalCount'))->render();
            return response()->json(['html' => $view, 'totalCount' => $totalCount]);
        }

        return view('customer.admin.list', compact('model', 'totalCount'));
    }


    public function admin_customer_create()
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        return view('customer.admin.create');
    }


    public function admin_customer_store(Request $request)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $request->merge(['phone' => Mobile::normalize($request->phone)]);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'required|regex:/^09\d{9}$/|unique:customers,phone',
            'password'   => 'nullable|string|min:6',
            'status'     => 'required|boolean',
        ], [
            'phone.regex'  => 'شماره موبایل باید ۱۱ رقم و به شکل ۰۹۱۲۳۴۵۶۷۸۹ باشد.',
            'phone.unique' => 'این شماره موبایل قبلا ثبت شده است.',
            'password.min' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = [
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'phone'      => $request->phone,
            'status'     => $request->status,
        ];

        // رمز اختیاری است؛ ورود مشتری با کد پیامکی هم ممکن است. قبلا اینجا
        // bcrypt(null) ذخیره می‌شد، یعنی هر مشتریِ ساخته‌شده در پنل «رمز دارد»
        // به حساب می‌آمد و صفحه‌ی ورود رمزی می‌خواست که هیچ‌کس نمی‌دانست.
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        Customer::create($data);

        return redirect('/admin/customer/list')
            ->with('success', 'مشتری با موفقیت ایجاد شد');
    }


    /** فرم ویرایش */
    public function admin_customer_edit($id)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $model = Customer::findOrFail($id);
        return view('customer.admin.create', compact('model'));
    }

    /** ویرایش */
    public function admin_customer_update(Request $request, $id)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $customer = Customer::findOrFail($id);

        $request->merge(['phone' => Mobile::normalize($request->phone)]);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => "required|regex:/^09\d{9}$/|unique:customers,phone,$id",
            'status'     => 'required|boolean',
        ], [
            'phone.regex'  => 'شماره موبایل باید ۱۱ رقم و به شکل ۰۹۱۲۳۴۵۶۷۸۹ باشد.',
            'phone.unique' => 'این شماره موبایل برای مشتری دیگری ثبت شده است.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // رمز عبور در فرم جداگانه‌ی خودش تغییر می‌کند تا با ذخیره‌ی اطلاعات
        // پروفایل به‌طور ناخواسته بازنویسی نشود.
        $customer->update($request->only(['first_name', 'last_name', 'phone', 'email', 'status']));

        return redirect('/admin/customer/list')
            ->with('success', 'مشتری با موفقیت بروزرسانی شد');
    }

    /**
     * تعیین یا حذف رمز عبور مشتری توسط پشتیبانی.
     *
     * مشتری‌ای که رمزش را فراموش کرده و به پیامک هم دسترسی ندارد، تنها راهش
     * همین است. با حذف رمز، ورود به مسیر کد یکبارمصرف برمی‌گردد.
     */
    public function admin_customer_password(Request $request, $id)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $customer = Customer::findOrFail($id);

        if ($request->input('action') === 'remove') {
            $customer->password = null;
            $customer->save();

            return back()->with('success', 'رمز عبور حذف شد؛ ورود مشتری فقط با کد پیامکی انجام می‌شود.');
        }

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'confirmed', PasswordRule::min(6)],
        ], [
            'password.required'  => 'رمز عبور جدید را وارد کنید.',
            'password.confirmed' => 'تکرار رمز عبور با رمز جدید یکسان نیست.',
            'password.min'       => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $customer->password = Hash::make($request->password);
        $customer->save();

        Log::info('Customer password changed by admin', [
            'admin_id'    => Auth::id(),
            'customer_id' => $customer->id,
        ]);

        return back()->with('success', 'رمز عبور مشتری تغییر کرد. آن را به خود مشتری اطلاع دهید.');
    }


    /** حذف */
    public function admin_customer_destroy($id)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        Customer::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
