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

        if ($inCart > 0) {
            $cart[$product->id]['quantity'] = $inCart + $quantity;
            $cart[$product->id]['price']    = $product->price;
        } else {
            $cart[$product->id] = [
                'title'    => $product->title,
                'price'    => $product->price,
                'quantity' => $quantity,
                'image'    => $product->image(),
                'url'      => $product->url(),
            ];
        }

        session()->put('cart', $cart);

        return response()->json([
            'status'     => 'success',
            'quantity'   => $cart[$product->id]['quantity'],
            'cart_count' => count($cart),
        ]);
    }

    public function getCart()
    {
        $cart = session()->get('cart', []);
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
        $cart = session()->get('cart', []);
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
            $cart[$id]['price'] = $product ? $product->price : $cart[$id]['price'];
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
