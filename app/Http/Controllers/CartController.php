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
            $cart[$product->id]['quantity']      = $inCart + $quantity;
            $cart[$product->id]['price']         = $product->sellablePrice();
            $cart[$product->id]['contact_price'] = $contactPrice;
        } else {
            $cart[$product->id] = [
                'title'         => $product->title,
                'price'         => $product->sellablePrice(),
                'contact_price' => $contactPrice,
                'quantity'      => $quantity,
                'image'         => $product->image(),
                'url'           => $product->url(),
            ];
        }

        session()->put('cart', $cart);

        return response()->json([
            'status'        => 'success',
            'quantity'      => $cart[$product->id]['quantity'],
            'cart_count'    => count($cart),
            'contact_price' => $contactPrice,
            'message'       => $contactPrice
                ? 'قطعه به سبد اضافه شد؛ قیمت این قطعه تلفنی اعلام می‌شود'
                : null,
        ]);
    }

    /**
     * سبدهایی که پیش از افزوده‌شدن «قطعات استعلامی» ساخته شده‌اند کلید
     * contact_price ندارند و ممکن است قیمت قطعه‌ی بدنه و شاسی را نگه داشته
     * باشند. این متد یک‌بار آن ردیف‌ها را با محصول واقعی هماهنگ می‌کند.
     */
    private static function normalizeCart(array $cart): array
    {
        $stale = array_keys(array_filter($cart, fn ($item) => !array_key_exists('contact_price', $item)));
        if (!$stale) {
            return $cart;
        }

        $products = Product::with('categories')->whereIn('id', $stale)->get()->keyBy('id');

        foreach ($stale as $id) {
            $product = $products->get($id);
            $cart[$id]['contact_price'] = $product ? $product->isContactPrice() : false;
            if ($product) {
                $cart[$id]['price'] = $product->sellablePrice();
            }
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
                $cart[$id]['price']         = $product->sellablePrice();
                $cart[$id]['contact_price'] = $product->isContactPrice();
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
