<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Cart;
use App\Support\Ecom;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        // stock may have moved since these items went in the cart
        $changes = Cart::sync();

        return view('cart.index', [
            'stockNotice' => $changes ? implode(' ', $changes) : null,
            'items' => Cart::items(),
            'subtotal' => Cart::subtotal(),
            'shipping' => Cart::shipping(),
            'tax' => Cart::tax(),
            'total' => Cart::total(),
        ]);
    }

    public function add(Request $request, Product $product)
    {
        $qty = (int) $request->input('qty', 1);
        $result = Cart::add($product->id, $qty);

        // nothing could be added, or less than asked for
        if ($result['qty'] === 0) {
            return $this->respond($request, $product->name.' is out of stock.');
        }
        if ($result['capped']) {
            return $this->respond($request, 'Only '.$result['available'].' of '.$product->name.' in stock — your cart now has '.$result['qty'].'.');
        }

        $track = Ecom::addToCart($product, $qty);

        // "Buy Now": add and go straight to checkout (native, non-AJAX submit).
        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout.index')->with('ecom_add_to_cart', $track);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return $this->respond($request, $product->name.' added to your cart.', ['track' => $track]);
        }

        return $this->respond($request, $product->name.' added to your cart.')->with('ecom_add_to_cart', $track);
    }

    public function update(Request $request, Product $product)
    {
        $result = Cart::update($product->id, (int) $request->input('qty', 1));

        $flash = null;
        if ($result['capped'] && $result['qty'] > 0) {
            $flash = 'Only '.$result['available'].' of '.$product->name.' in stock.';
        } elseif ($result['capped']) {
            $flash = $product->name.' is out of stock.';
        }

        return $this->respond($request, $flash);
    }

    public function remove(Request $request, Product $product)
    {
        Cart::remove($product->id);

        return $this->respond($request, 'Item removed from cart.');
    }

    public function clear(Request $request)
    {
        Cart::clear();

        return $this->respond($request, 'Cart cleared.');
    }

    /**
     * AJAX requests get JSON (cart totals + the re-rendered drawer HTML);
     * normal requests fall back to a redirect so the cart works without JS.
     */
    private function respond(Request $request, ?string $flash = null, array $extra = [])
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'count' => Cart::count(),
                'subtotal' => Cart::subtotal(),
                'total' => Cart::total(),
                'flash' => $flash,
                'html' => view('cart.partials.contents')->render(),
            ] + $extra);
        }

        return redirect()->route('cart.index')->with('status', $flash);
    }
}
