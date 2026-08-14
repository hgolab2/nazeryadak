<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Product;

class CartController extends Controller
{
    public function add(Request $request)
    {
        // ناموجودی از «نبودن محصول» جدا شده است؛ قبلا هر دو حالت پیام
        // گمراه‌کننده‌ی «محصول یافت نشد» می‌گرفتند
        $product = Product::where('id', $request->product_id)->where('is_active', 1)->first();
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'محصول یافت نشد'], 404);
        }

        $stock = (int) $product->stock;
        if ($stock <= 0) {
            return response()->json(['status' => 'error', 'message' => 'این محصول در حال حاضر موجود نیست'], 422);
        }

        $quantity = (int) $request->input('quantity', 1);
        $quantity = max(1, min($quantity, $stock));

        $cart = session()->get('cart', []);
        $inCart = (int) ($cart[$product->id]['quantity'] ?? 0);

        if ($inCart + $quantity > $stock) {
            $remaining = max(0, $stock - $inCart);
            $message = $remaining > 0
                ? 'تنها ' . toPersianNumbers($remaining, false) . ' عدد از این محصول موجود است'
                : 'همه‌ی موجودی این محصول در سبد شماست';

            return response()->json(['status' => 'error', 'message' => $message], 422);
        }

        // قطعات بدنه و شاسی قیمت اعلامی ندارند: مبلغشان صفر ذخیره می‌شود تا
        // نه در جمع سبد بیاید و نه از طریق /cart/data به کاربر نشت کند.
        $contactPrice = $product->isContactPrice();

        if ($inCart > 0) {
            $cart[$product->id]['quantity'] = $inCart + $quantity;
        } else {
            $cart[$product->id] = [
                'title'    => $product->title,
                'quantity' => $quantity,
                'image'    => $product->image(),
                'url'      => $product->url(),
            ];
        }

        self::syncLine($cart, $product);
        session()->put('cart', $cart);

        $line = $cart[$product->id];

        return response()->json([
            'status'        => 'success',
            'quantity'      => $line['quantity'],
            'cart_count'    => count($cart),
            'contact_price' => $contactPrice,
            'is_wholesale'  => (bool) ($line['is_wholesale'] ?? false),
            'message'       => $contactPrice
                ? 'قطعه به سبد اضافه شد؛ قیمت این قطعه تلفنی اعلام می‌شود'
                : ($line['is_wholesale'] ?? false ? 'قیمت عمده برای این قطعه اعمال شد' : null),
        ]);
    }

    /**
     * قیمت یک ردیف سبد را با تعداد فعلی هماهنگ می‌کند.
     *
     * قیمت واحد به تعداد وابسته است (عمده/تکی)، پس هر بار که تعداد عوض
     * می‌شود باید دوباره از روی محصول خوانده شود؛ وگرنه کاربر می‌توانست با
     * رسیدن به تعداد عمده قیمت تکی بپردازد یا برعکس.
     */
    private static function syncLine(array &$cart, Product $product): void
    {
        $id  = $product->id;
        $qty = max(1, (int) ($cart[$id]['quantity'] ?? 1));

        $cart[$id]['quantity']          = $qty;
        $cart[$id]['price']             = $product->unitPriceFor($qty);
        $cart[$id]['contact_price']     = $product->isContactPrice();
        $cart[$id]['unit_price']        = $product->sellablePrice();
        $cart[$id]['wholesale_min_qty'] = $product->hasWholesale() ? $product->wholesaleMinQty() : null;
        $cart[$id]['wholesale_price']   = $product->hasWholesale() ? $product->wholesalePrice() : null;
        $cart[$id]['is_wholesale']      = $product->hasWholesale() && $qty >= $product->wholesaleMinQty();
    }

    /**
     * سبدهای ساخته‌شده پیش از «قطعات استعلامی» یا پیش از «فروش عمده» کلیدهای
     * تازه را ندارند و ممکن است قیمتِ نادرست نگه داشته باشند. این متد آن
     * ردیف‌ها را یک‌بار با محصول واقعی هماهنگ می‌کند.
     */
    private static function normalizeCart(array $cart): array
    {
        $stale = array_keys(array_filter(
            $cart,
            fn ($item) => !array_key_exists('contact_price', $item) || !array_key_exists('is_wholesale', $item)
        ));

        if (!$stale) {
            return $cart;
        }

        $products = Product::with('categories')->whereIn('id', $stale)->get()->keyBy('id');

        foreach ($stale as $id) {
            $product = $products->get($id);

            if (! $product) {
                $cart[$id]['contact_price'] = false;
                $cart[$id]['is_wholesale']  = false;
                continue;
            }

            self::syncLine($cart, $product);
        }

        session()->put('cart', $cart);

        return $cart;
    }

    public function getCart()
    {
        $cart = self::normalizeCart(session()->get('cart', []));
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return response()->json([
            'items' => $cart,
            'count' => count($cart),
            'total' => number_format($total),
        ]);
    }

    public function remove(Request $request)
    {
        $id = $request->id;
        $cart = session()->get('cart', []);

        unset($cart[$id]);
        session()->put('cart', $cart);

        $cartTotal = array_sum(array_map(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1), $cart));

        return response()->json([
            'status'     => 'success',
            'cart_total'  => $cartTotal,
            'cart_count'  => count($cart),
            'items_count' => self::itemsCount($cart),
        ]);
    }

    public function cart()
    {
        $cart = self::normalizeCart(session()->get('cart', []));
        return view('product.cart', compact('cart'));
    }

    public function increaseQty(Request $request)
    {
        $id = $request->id;
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            $product = Product::find($id);
            if ($product && $product->stock > 0 && $cart[$id]['quantity'] >= $product->stock) {
                return response()->json(['status' => 'error', 'message' => 'موجودی کافی نیست'], 422);
            }
            $cart[$id]['quantity']++;
            if ($product) {
                self::syncLine($cart, $product);
            }
            session()->put('cart', $cart);
        }

        return $this->cartItemResponse($cart, $id);
    }

    public function decreaseQty(Request $request)
    {
        $id = $request->id;
        $cart = session()->get('cart', []);

        if (isset($cart[$id]) && ($cart[$id]['quantity'] ?? 1) > 1) {
            $cart[$id]['quantity']--;
            // با پایین‌آمدن از آستانه، قیمت باید به تکی برگردد
            if ($product = Product::find($id)) {
                self::syncLine($cart, $product);
            }
            session()->put('cart', $cart);
        }

        return $this->cartItemResponse($cart, $id);
    }

    private function cartItemResponse($cart, $id)
    {
        $itemSubtotal = ($cart[$id]['price'] ?? 0) * ($cart[$id]['quantity'] ?? 1);
        $cartTotal = array_sum(array_map(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1), $cart));

        return response()->json([
            'status'        => 'success',
            'item_quantity'  => $cart[$id]['quantity'] ?? 0,
            'item_subtotal'  => $itemSubtotal,
            'item_unit_price' => (int) ($cart[$id]['price'] ?? 0),
            // آیا این ردیف با قیمت عمده حساب شده است؟ صفحه‌ی سبد نشان می‌دهد
            'item_is_wholesale' => (bool) ($cart[$id]['is_wholesale'] ?? false),
            'item_wholesale_min_qty' => $cart[$id]['wholesale_min_qty'] ?? null,
            // ردیف استعلامی مبلغ ندارد؛ جاوااسکریپت به‌جای عدد «تماس بگیرید» می‌نویسد
            'item_contact_price' => (bool) ($cart[$id]['contact_price'] ?? false),
            'cart_total'     => $cartTotal,
            'cart_count'     => count($cart),
            // مجموع تعداد قطعات؛ صفحه‌ی سبد این عدد را نشان می‌دهد نه تعداد ردیف‌ها
            'items_count'    => self::itemsCount($cart),
        ]);
    }

    private static function itemsCount(array $cart): int
    {
        return (int) array_sum(array_map(fn($i) => $i['quantity'] ?? 1, $cart));
    }
}
