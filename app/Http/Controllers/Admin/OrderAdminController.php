<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\OrderItem;
use App\Models\Order;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Support\Mobile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Facades\Auth;
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

        $query = Order::with(['customer', 'items']);

        // فیلترها
        if ($request->filled('order_id')) {
            $query->where('id', $request->order_id);
        }

        if ($request->filled('phone')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('phone', 'like', "%{$request->phone}%");
            });
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

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'address_id' => 'nullable|exists:customer_addresses,id',
            'shipping_method_id' => 'required|exists:shipping_methods,id',
            'shipping_price' => 'nullable|integer|min:0',
            'total_price' => 'required|integer|min:0',
            'final_price' => 'required|integer|min:0',
            'status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        Order::create($request->only([
            'customer_id',
            'address_id',
            'shipping_method_id',
            'shipping_price',
            'total_price',
            'final_price',
            'status',
        ]));

        return redirect('/admin/orders/list')
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

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'address_id' => 'nullable|exists:customer_addresses,id',
            'shipping_method_id' => 'required|exists:shipping_methods,id',
            'shipping_price' => 'nullable|integer|min:0',
            'total_price' => 'required|integer|min:0',
            'final_price' => 'required|integer|min:0',
            'status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $previousStatus = $order->status;

        $order->update($request->only([
            'customer_id',
            'address_id',
            'shipping_method_id',
            'shipping_price',
            'total_price',
            'final_price',
            'status',
        ]));

        // اطلاع‌رسانی فقط وقتی وضعیت واقعا عوض شده باشد؛ ویرایش مبلغ یا آدرس
        // نباید برای مشتری پیامک بفرستد. خطای درگاه هم نباید ذخیره را بشکند.
        if ($previousStatus !== $order->status) {
            try {
                (new \App\Services\OrderNotifier())->statusChanged($order->fresh('customer'), $previousStatus);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('اطلاع‌رسانی تغییر وضعیت سفارش ناموفق بود', [
                    'order_id' => $order->id,
                    'message'  => $e->getMessage(),
                ]);
            }
        }

        return redirect('/admin/orders/list')
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

        Order::findOrFail($id)->delete();

        return response()->json(['success' => true]);
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
