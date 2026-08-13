<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\CustomerAddress;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    public function shopping()
    {
        $user = $this->customer();
        if (!$user) {
            // بدون این، کاربری که وسط خرید بود بعد از ورود در داشبورد رها می‌شد
            session()->put('url.intended', '/order/shopping');
            return redirect('/login');
        }

        $cart = session()->get('cart', []);
        if (count($cart) == 0) {
            return redirect('/cart');
        }

        $this->validateCartStock($cart);

        $order = Order::firstOrCreate(
            ['customer_id' => $user->id, 'status' => 'pending'],
            ['total_price' => 0, 'final_price' => 0, 'shipping_price' => 0]
        );

        $order->items()->delete();
        $total = 0;

        foreach ($cart as $id => $item) {
            $product = Product::find($id);
            $price = $product ? $product->price : $item['price'];
            $quantity = $item['quantity'] ?? 1;

            $order->items()->create([
                'product_id' => $id,
                'quantity'   => $quantity,
                'unit_price' => $price,
                'total_price' => $price * $quantity,
            ]);
            $total += $price * $quantity;
        }

        $shippingInfo = getShippingInfo($order);
        $shippingPrice = $shippingInfo['cost'];
        $order->update([
            'final_price'    => $total,
            'shipping_price' => $shippingPrice,
            'total_price'    => $total + $shippingPrice,
        ]);

        $provinces = Province::orderBy('name')->get();
        $address = CustomerAddress::where('customer_id', $user->id)->first();

        return view('order.shopping', [
            'order'          => $order,
            'provinces'      => $provinces,
            'address'        => $address,
            'shipping_price' => $shippingPrice,
            'shipping_info'  => $shippingInfo,
        ]);
    }

    public function storeOrUpdateAddress(Request $request)
    {
        $user = $this->customer();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'لطفا وارد شوید'], 401);
        }

        $data = $request->validate([
            'receiver_name'  => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'province_id'    => 'required|exists:provinces,id',
            'city'           => 'required|string|max:255',
            'postal_code'    => 'nullable|string|max:10',
            'address_line'   => 'required|string|max:1000',
        ]);

        $address = CustomerAddress::updateOrCreate(
            ['customer_id' => $user->id],
            $data
        );

        $address->load('province');

        $html = view('order.address-preview', compact('address'))->render();
        return response()->json(['status' => 'success', 'html' => $html]);
    }

    public function payment(Request $request, $id)
    {
        $user = $this->customer();
        if (!$user) {
            // مقصد را نگه دار تا بعد از ورود کاربر به همین‌جا برگردد
            session()->put('url.intended', '/order/payment/' . $id);
            return redirect('/login');
        }

        // 'failed' هم پذیرفته می‌شود تا دکمه‌ی «تلاش مجدد پرداخت» کار کند؛
        // قبلا کاربر بعد از پرداخت ناموفق بی‌هیچ توضیحی به لیست سفارش‌ها پرت می‌شد
        $order = Order::where('id', $id)
            ->where('customer_id', $user->id)
            ->whereIn('status', ['pending', 'failed'])
            ->first();

        if (!$order) {
            return redirect('/profile/orders')
                ->with('error', 'این سفارش برای پرداخت در دسترس نیست.');
        }

        $address = CustomerAddress::where('customer_id', $user->id)->first();
        if (!$address) {
            return redirect('/order/shopping')->with('error', 'لطفا ابتدا آدرس تحویل را ثبت کنید.');
        }

        $order->update(['address_id' => $address->id]);
        $address->load('province');
        $shippingInfo = getShippingInfo($order);

        return view('order.payment', compact('order', 'address', 'shippingInfo'));
    }

    public function confirmOrder(Request $request)
    {
        $user = $this->customer();
        if (!$user) {
            return redirect('/login');
        }

        $order = Order::where('customer_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->firstOrFail();

        session()->forget('cart');

        return redirect('/order/payment/' . $order->id);
    }

    public function calcShipping(Request $request)
    {
        $user = $this->customer();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        $order = Order::where('id', $request->order_id)
            ->where('customer_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['status' => 'error'], 404);
        }

        $shippingInfo = getShippingInfo($order);
        $shippingCost = $shippingInfo['cost'];

        $order->update([
            'shipping_price' => $shippingCost,
            'total_price'    => $order->final_price + $shippingCost,
        ]);

        return response()->json([
            'status'         => 'success',
            'shipping_price' => $shippingCost,
            'shipping_label' => $shippingInfo['label'],
            'shipping_type'  => $shippingInfo['type'],
            'total_price'    => $order->final_price + $shippingCost,
        ]);
    }

    private function validateCartStock(&$cart)
    {
        $changed = false;
        foreach ($cart as $id => &$item) {
            $product = Product::find($id);
            if (!$product || !$product->is_active || $product->stock <= 0) {
                unset($cart[$id]);
                $changed = true;
                continue;
            }
            if ($item['quantity'] > $product->stock) {
                $item['quantity'] = $product->stock;
                $changed = true;
            }
            $item['price'] = $product->price;
        }
        if ($changed) {
            session()->put('cart', $cart);
        }
    }
}
