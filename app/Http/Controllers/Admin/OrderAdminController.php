<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\OrderItem;
use App\Models\Order;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Auth;
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

        $order->update($request->only([
            'customer_id',
            'address_id',
            'shipping_method_id',
            'shipping_price',
            'total_price',
            'final_price',
            'status',
        ]));

        return redirect('/admin/orders/list')
            ->with('success', 'سفارش با موفقیت بروزرسانی شد');
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

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'required|string|max:20|unique:customers,phone',
            /*'email'      => 'nullable|email|max:255|unique:customers,email',
            'password'   => 'required|string|min:6',*/
            'status'     => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = [
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'phone'      => $request->phone,
            //'email'      => $request->email,
            'status'     => $request->status,
            'password'   => bcrypt($request->password),
        ];

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

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => "required|string|max:20|unique:customers,phone,$id",
            /*'email'      => "nullable|email|max:255|unique:customers,email,$id",
            'password'   => 'nullable|string|min:6',*/
            'status'     => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only([
            'first_name',
            'last_name',
            'phone',
            'email',
            'status',
        ]);

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $customer->update($data);

        return redirect('/admin/customer/list')
            ->with('success', 'مشتری با موفقیت بروزرسانی شد');
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
