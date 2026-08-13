<?php

namespace App\Http\Controllers;
use App\Models\Order;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    function shopping(Request $request)
    {
        return View('order.shopping');
    }

    function payment(Request $request)
    {
        return View('order.payment');
    }

    function success(Request $request)
    {
        return View('order.success');
    }

    function orders(Request $request)
    {
        $user = Auth::guard('customer')->user();

        if (! $user) {
            return redirect('/login');
        }
        // کارت سفارش تعداد اقلام را نشان می‌دهد؛ بدون eager load به ازای هر
        // سفارش یک کوئری جدا اجرا می‌شد.
        $orders = Order::with('items')->where('customer_id', $user->id)->latest()->paginate(10);
        return View('order.orders', compact('orders'));
    }

    function orderDetails(Request $request , $id)
    {
        $user = Auth::guard('customer')->user();

        if (! $user) {
            return redirect('/login');
        }
        $order = Order::where('customer_id', $user->id)->where('id', $id)->first();
        return View('order.orderItems', compact('order'));
    }

    function view(Request $request, $id = null)
    {
        if ($id) {
            return redirect('/profile/orderDetail/' . $id);
        }
        return redirect('/profile/orders');
    }

    function info(Request $request)
    {
        $user = Auth::guard('customer')->user();

        if (! $user) {
            return redirect('/login');
        }
        $customer = Customer::where('id', $user->id)->first();
        $address = CustomerAddress::where('customer_id', $user->id)->first();
        $provinces = Province::get();

        return View('order.info', compact('customer' , 'address' , 'provinces'));
    }

    public function infoUpdate(Request $request)
    {
        $user = Auth::guard('customer')->user();

        if (! $user) {
            return redirect('/login');
        }
        $customer = Customer::where('id', $user->id)->first();

        if (! $customer) {
            abort(403);
        }

        $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'province_id'    => 'required|exists:provinces,id',
            'city'           => 'required|string|max:255',
            'receiver_phone' => 'nullable|string|max:20',
            'postal_code'    => 'nullable|string|max:20',
            'address_line'   => 'required|string',
        ]);

        /** ذخیره اطلاعات مشتری **/
        $customer->update([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
        ]);

        /** ذخیره یا بروزرسانی آدرس **/
        CustomerAddress::updateOrCreate(
            ['customer_id' => $customer->id],
            [
                'province_id'    => $request->province_id,
                'city'           => $request->city,
                'receiver_phone' => $request->receiver_phone,
                'postal_code'    => $request->postal_code,
                'address_line'   => $request->address_line,
                'is_default'     => true,
            ]
        );

        return redirect()
            ->route('dashboard')
            ->with('success', 'اطلاعات با موفقیت ذخیره شد');
    }

}
