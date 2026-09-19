<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuyNowController extends Controller
{
    /**
     * Start a checkout that is intentionally separate from the persistent cart.
     */
    public function start(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::query()
            ->whereKey($product->getKey())
            ->where('is_active', true)
            ->firstOrFail();

        $quantity = (int) ($validated['quantity'] ?? 1);

        if ($quantity > (int) $product->stock) {
            return back()->with('error', 'Số lượng đặt hàng vượt quá số lượng hoa hiện có.');
        }

        $request->session()->put('buy_now', [
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);

        if (!Auth::check()) {
            return redirect()->guest(route('login'));
        }

        return redirect()->route('buy-now.checkout');
    }
}
