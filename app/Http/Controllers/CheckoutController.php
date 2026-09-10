<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\CustomerAddress;
use App\Models\Province;
use App\Support\Mobile;
use App\Services\OrderNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /*
    | خرید بدون حساب.
    |
    | تا حالا مسیر تسویه با redirect('/login') بسته بود؛ یعنی مشتری باید
    | شماره می‌داد، منتظر پیامک می‌ماند و کد می‌زد — و تازه بعدش می‌فهمید
    | کرایه‌ی ارسال چقدر است. آن انتظار، نقطه‌ای بود که بیشترین ریزش را داشت.
    |
    | حالا سفارش بدون هیچ ورودی ثبت می‌شود و شماره جایی گرفته می‌شود که
    | خودش لازم است: فرم آدرس، برای هماهنگی تحویل. کد تأیید بعد از ثبت
    | سفارش در پیامک می‌رود و اختیاری است.
    |
    | قاعده‌ای که نباید شکسته شود: بدون کد پیامکی می‌شود سفارش داد، ولی
    | هیچ‌وقت نباید نشستِ ورود ساخته شود. ورود همچنان فقط از مسیر OTP.
    */

    /** سفارش‌هایی که همین مرورگر ساخته است. */
    private const GUEST_ORDERS = 'guest_orders';

    /** مشتریِ واردشده، اگر هست. */
    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    /**
     * خریدارِ این سبد.
     *
     * برای مهمان تا وقتی آدرس ثبت نشده null است و این درست است: سفارشِ
     * بی‌صاحب هم مجاز است، چون ستون orders.customer_id nullable است.
     */
    private function buyer(): ?Customer
    {
        if ($user = $this->customer()) {
            return $user;
        }

        $order = $this->guestOrder();

        return $order && $order->customer_id ? $order->customer : null;
    }

    /** سفارشِ در حال ساختِ این مرورگر (فقط برای مهمان). */
    private function guestOrder(): ?Order
    {
        $ids = (array) session()->get(self::GUEST_ORDERS, []);

        if (! $ids) {
            return null;
        }

        return Order::with('customer')
            ->whereIn('id', $ids)
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }

    /** ثبت مالکیتِ این مرورگر روی سفارش. */
    private function rememberOrder(Order $order): void
    {
        $ids = (array) session()->get(self::GUEST_ORDERS, []);

        if (! in_array($order->id, $ids, true)) {
            $ids[] = $order->id;
            session()->put(self::GUEST_ORDERS, array_slice($ids, -5));
        }
    }

    /**
     * سفارشی که این مرورگر حق دیدنش را دارد.
     *
     * برای کاربر واردشده ملاک customer_id است؛ برای مهمان، اینکه سفارش را
     * در همین نشست ساخته باشد. حدس‌زدن شماره‌ی سفارش نباید کافی باشد.
     */
    private function ownedOrder($id, array $statuses = []): ?Order
    {
        $query = Order::where('id', $id);

        if ($statuses) {
            $query->whereIn('status', $statuses);
        }

        if ($user = $this->customer()) {
            return $query->where('customer_id', $user->id)->first();
        }

        $ids = (array) session()->get(self::GUEST_ORDERS, []);

        return in_array((int) $id, array_map('intval', $ids), true) ? $query->first() : null;
    }

    /**
     * آدرسِ این سفارش.
     *
     * برای مهمان عمدا از روی خودِ سفارش خوانده می‌شود، نه از customer_id:
     * اگر کسی شماره‌ی یک مشتری دیگر را وارد کند، نباید آدرس ذخیره‌شده‌ی
     * آن آدم را ببیند.
     */
    private function addressFor(Order $order): ?CustomerAddress
    {
        if ($order->address_id && $order->address) {
            return $order->address;
        }

        $user = $this->customer();

        return $user ? CustomerAddress::where('customer_id', $user->id)->first() : null;
    }

    public function shopping()
    {
        $cart = session()->get('cart', []);
        if (count($cart) == 0) {
            return redirect('/cart');
        }

        $this->validateCartStock($cart);

        $user = $this->customer();

        if ($user) {
            $order = Order::firstOrCreate(
                ['customer_id' => $user->id, 'status' => 'pending'],
                ['total_price' => 0, 'final_price' => 0, 'shipping_price' => 0]
            );
        } else {
            // مهمان: سفارش هنوز صاحبی ندارد. صاحبش در فرم آدرس مشخص می‌شود،
            // جایی که شماره‌ی تماس به‌هرحال لازم است.
            $order = $this->guestOrder() ?: Order::create([
                'status'         => 'pending',
                'total_price'    => 0,
                'final_price'    => 0,
                'shipping_price' => 0,
            ]);
        }

        $this->rememberOrder($order);

        $order->items()->delete();
        $total = 0;

        foreach ($cart as $id => $item) {
            $product = Product::find($id);
            $quantity = $item['quantity'] ?? 1;
            // قطعات استعلامی (بدنه و شاسی) با مبلغ صفر ثبت می‌شوند تا در جمع
            // فاکتور نیایند؛ مبلغشان بعدا تلفنی هماهنگ و به فاکتور اضافه می‌شود.
            // قیمت از روی تعداد خوانده می‌شود تا خرید عمده در فاکتور هم اعمال شود.
            $price = $product ? $product->unitPriceFor($quantity) : (int) ($item['price'] ?? 0);

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

        // سبد از بازدید قبلی عوض شده باشد، تخفیف هم باید دوباره حساب شود؛
        // syncDiscount خودش total_price را با کسر تخفیف می‌نویسد.
        $order->final_price    = $total;
        $order->shipping_price = $shippingPrice;
        $discountNotice        = $order->syncDiscount();
        $order->save();

        $provinces = Province::orderBy('name')->get();
        $address   = $this->addressFor($order);

        return view('order.shopping', [
            'order'          => $order,
            'provinces'      => $provinces,
            'address'        => $address,
            'shipping_price' => $shippingPrice,
            'shipping_info'  => $shippingInfo,
            'discountNotice' => $discountNotice,
        ]);
    }

    public function storeOrUpdateAddress(Request $request)
    {
        // شماره‌ی گیرنده با کیبورد فارسی یا +۹۸ هم می‌آید؛ بدون یکسان‌سازی،
        // هر بار یک مشتریِ تکراری با شکل دیگری از همان شماره ساخته می‌شد.
        $request->merge(['receiver_phone' => Mobile::normalize($request->input('receiver_phone'))]);

        $data = $request->validate([
            'receiver_name'  => 'required|string|max:255',
            'receiver_phone' => 'required|regex:/^09\d{9}$/',
            'province_id'    => 'required|exists:provinces,id',
            'city'           => 'required|string|max:255',
            'postal_code'    => 'nullable|string|max:10',
            'address_line'   => 'required|string|max:1000',
        ], [
            'receiver_phone.regex' => 'شماره تماس باید ۱۱ رقم و به شکل ۰۹۱۲۳۴۵۶۷۸۹ باشد.',
        ]);

        $user = $this->customer();

        if ($user) {
            $address = CustomerAddress::updateOrCreate(['customer_id' => $user->id], $data);
        } else {
            $order = $this->guestOrder();

            if (! $order) {
                return response()->json(['status' => 'error', 'message' => 'سبد خرید شما منقضی شده است.'], 422);
            }

            /*
            | اینجا مهمان صاحب پیدا می‌کند. شماره کلیدِ هویت است، پس اگر از
            | قبل حسابی با همین شماره باشد سفارش به همان می‌چسبد و بعدا با
            | تأیید شماره، سابقه‌اش سر جایش است — چیزی برای «انتقال» نمی‌ماند.
            |
            | ولی آدرس عمدا به‌جای updateOrCreate با create ساخته می‌شود:
            | اگر کسی شماره‌ی یک مشتری دیگر را وارد کند، نباید آدرس
            | ذخیره‌شده‌ی آن آدم بازنویسی شود.
            */
            $customer = Customer::firstOrCreate(
                ['phone' => $data['receiver_phone']],
                ['status' => 1]
            );

            // نامِ خالی را از همین فرم پر می‌کنیم؛ حسابِ بی‌نام در پنل مدیریت
            // فقط یک شماره است و کارشناس نمی‌داند با چه کسی طرف است.
            if ($customer->fullName() === '') {
                $parts = preg_split('/\s+/u', trim($data['receiver_name']), 2);
                $customer->forceFill([
                    'first_name' => $parts[0] ?? '',
                    'last_name'  => $parts[1] ?? '',
                ])->save();
            }

            $address = $order->address_id && $order->address
                ? tap($order->address)->update($data)
                : CustomerAddress::create($data + ['customer_id' => $customer->id]);

            $order->customer_id = $customer->id;
            $order->address_id  = $address->id;
            $order->save();
        }

        $address->load('province');

        $html = view('order.address-preview', compact('address'))->render();
        return response()->json(['status' => 'success', 'html' => $html]);
    }

    public function payment(Request $request, $id)
    {
        // 'failed' هم پذیرفته می‌شود تا دکمه‌ی «تلاش مجدد پرداخت» کار کند؛
        // قبلا کاربر بعد از پرداخت ناموفق بی‌هیچ توضیحی به لیست سفارش‌ها پرت می‌شد
        $order = $this->ownedOrder($id, ['pending', 'failed']);

        if (!$order) {
            return redirect($this->customer() ? '/profile/orders' : '/cart')
                ->with('error', 'این سفارش برای پرداخت در دسترس نیست.');
        }

        $address = $this->addressFor($order);
        if (!$address) {
            return redirect('/order/shopping')->with('error', 'لطفا ابتدا آدرس تحویل را ثبت کنید.');
        }

        $order->address_id = $address->id;
        // کد ممکن است بین مرحله‌ی قبل و اینجا منقضی یا غیرفعال شده باشد
        $discountNotice = $order->syncDiscount();
        $order->save();

        $address->load('province');
        $shippingInfo = getShippingInfo($order);

        // وقتی پرداخت آنلاین خاموش است، این صفحه به «بازبینی و ثبت نهایی سفارش»
        // تبدیل می‌شود و به‌جای درگاه، پیش‌فاکتور صادر می‌شود.
        $onlinePayment = onlinePaymentEnabled();
        $hasContactPriceItems = $order->hasContactPriceItems();

        // سفارشی که قلم استعلامی دارد مبلغ کامل ندارد و نباید به درگاه برود،
        // حتی اگر پرداخت آنلاین روشن باشد.
        $canPayOnline = $onlinePayment && ! $hasContactPriceItems;

        return view('order.payment', compact(
            'order', 'address', 'shippingInfo', 'onlinePayment', 'hasContactPriceItems', 'canPayOnline',
            'discountNotice'
        ));
    }

    /**
     * ثبت نهایی سفارش بدون پرداخت آنلاین.
     *
     * سفارش با وضعیت «در انتظار تماس کارشناس» ثبت می‌شود، سبد خالی می‌شود و
     * کاربر پیش‌فاکتور را می‌بیند. موجودی اینجا کم نمی‌شود؛ سفارش پس از تأیید
     * تلفنی و پرداخت، توسط ادمین به «پرداخت شده» تغییر وضعیت می‌دهد.
     */
    public function place(Request $request, $id)
    {
        $order = $this->ownedOrder($id, ['pending', 'failed']);

        if (!$order) {
            return redirect($this->customer() ? '/profile/orders' : '/cart')
                ->with('error', 'این سفارش برای ثبت در دسترس نیست.');
        }

        $address = $this->addressFor($order);
        if (!$address) {
            return redirect('/order/shopping')->with('error', 'لطفا ابتدا آدرس تحویل را ثبت کنید.');
        }

        // سفارشی که صاحب ندارد یعنی آدرس ثبت نشده؛ بدون شماره‌ی تماس،
        // کارشناس راهی برای پیگیری ندارد.
        if (! $order->customer_id) {
            return redirect('/order/shopping')->with('error', 'لطفا ابتدا آدرس تحویل را ثبت کنید.');
        }

        if ($order->items()->count() === 0) {
            return redirect('/cart')->with('error', 'سفارش شما قلمی ندارد.');
        }

        // آخرین فرصت برای اعتبارسنجی تخفیف؛ بعد از این سفارش ثبت است و
        // مبلغش سند می‌شود. اگر کد همین‌جا از دست برود، کاربر باید بداند
        // چرا مبلغ فاکتورش با چیزی که دید فرق دارد.
        $discountNotice = $order->syncDiscount();

        $order->address_id = $address->id;
        $order->status     = 'awaiting_call';
        $order->save();

        session()->forget('cart');

        // این مرورگر سفارش را ثبت کرد، پس حق دارد کد تأیید شماره را وارد کند.
        PhoneVerificationController::allow($order->id);

        try {
            (new OrderNotifier())->orderPlaced($order->fresh('customer'));
        } catch (\Throwable $e) {
            Log::error('اطلاع‌رسانی ثبت سفارش ناموفق بود', ['order_id' => $order->id, 'message' => $e->getMessage()]);
        }

        $adminPhone = config('payment.notify_mobile');
        if ($adminPhone) {
            try {
                sendSms(
                    $adminPhone,
                    "سفارش جدید (نیازمند تماس) #{$order->id}\n"
                    . $order->items()->count() . " قطعه\n"
                    . ($order->customer?->phone ?: '')
                );
            } catch (\Throwable $e) {
                Log::error('پیامک سفارش به ادمین ناموفق بود', ['order_id' => $order->id, 'message' => $e->getMessage()]);
            }
        }

        $redirect = redirect('/order/invoice/' . $order->id);

        return $discountNotice ? $redirect->with('error', $discountNotice) : $redirect;
    }

    /**
     * پیش‌فاکتور سفارش؛ سندی که مشتری تا تماس کارشناس در دست دارد.
     */
    public function invoice($id)
    {
        $order = $this->ownedOrder($id);

        if (!$order) {
            return redirect($this->customer() ? '/profile/orders' : '/cart')
                ->with('error', 'این سفارش پیدا نشد.');
        }

        $order->load(['items.product.categories', 'customer']);

        $address      = $this->addressFor($order);
        $shippingInfo = getShippingInfo($order);

        if ($address) {
            $address->load('province');
        }

        return view('order.invoice', compact('order', 'address', 'shippingInfo'));
    }

    public function confirmOrder(Request $request)
    {
        $user = $this->customer();

        $order = $user
            ? Order::where('customer_id', $user->id)->where('status', 'pending')->latest()->firstOrFail()
            : $this->guestOrder();

        if (! $order) {
            return redirect('/cart')->with('error', 'سبد خرید شما منقضی شده است.');
        }

        session()->forget('cart');

        return redirect('/order/payment/' . $order->id);
    }

    public function calcShipping(Request $request)
    {
        $order = $this->ownedOrder($request->order_id);

        if (!$order) {
            return response()->json(['status' => 'error'], 404);
        }

        $shippingInfo = getShippingInfo($order);
        $shippingCost = $shippingInfo['cost'];

        $order->shipping_price = $shippingCost;
        $discountNotice        = $order->syncDiscount();
        $order->save();

        return response()->json([
            'status'          => 'success',
            'shipping_price'  => $shippingCost,
            'shipping_label'  => $shippingInfo['label'],
            'shipping_type'   => $shippingInfo['type'],
            'discount_amount' => (int) $order->discount_amount,
            // اگر کد همین‌جا از اعتبار افتاده باشد، کادر تخفیف هم باید از
            // حالت «اعمال شد» بیرون بیاید؛ وگرنه دکمه‌ی حذفِ کدی می‌ماند که
            // دیگر روی سفارش نیست
            'discount_html'   => view('order.discount-box', ['order' => $order])->render(),
            'discount_notice' => $discountNotice,
            'total_price'     => (int) $order->total_price,
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
            // قیمت واحد به تعداد وابسته است (عمده/تکی)؛ اگر تعداد بالا به
            // خاطر موجودی کم شده باشد، قیمت هم باید به تکی برگردد.
            $item['price']             = $product->unitPriceFor((int) $item['quantity']);
            $item['contact_price']     = $product->isContactPrice();
            $item['wholesale_min_qty'] = $product->hasWholesale() ? $product->wholesaleMinQty() : null;
            $item['wholesale_price']   = $product->hasWholesale() ? $product->wholesalePrice() : null;
            $item['is_wholesale']      = $product->hasWholesale() && (int) $item['quantity'] >= $product->wholesaleMinQty();
        }
        if ($changed) {
            session()->put('cart', $cart);
        }
    }
}
