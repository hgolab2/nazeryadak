<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Product;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $product = Product::where('id', $request->product_id)->where('is_active', 1)->first();
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'محصول یافت نشد'], 404);
        }

        $cart = session()->get('cart', []);

        if (isset($cart[$product->id])) {
            if ($product->stock > 0 && $cart[$product->id]['quantity'] >= $product->stock) {
                return response()->json(['status' => 'error', 'message' => 'موجودی کافی نیست'], 422);
            }
            $cart[$product->id]['quantity']++;
        } else {
            $cart[$product->id] = [
                'title'    => $product->title,
                'price'    => $product->price,
                'quantity' => 1,
                'image'    => $product->image(),
                'url'      => $product->url(),
            ];
        }

        session()->put('cart', $cart);
        return response()->json(['status' => 'success']);
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
        $cartCount = count($cart);

        return response()->json([
            'status'     => 'success',
            'cart_total'  => $cartTotal,
            'cart_count'  => $cartCount,
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
        $cartCount = count($cart);

        return response()->json([
            'status'        => 'success',
            'item_quantity'  => $cart[$id]['quantity'] ?? 0,
            'item_subtotal'  => $itemSubtotal,
            'cart_total'     => $cartTotal,
            'cart_count'     => $cartCount,
        ]);
    }
}
