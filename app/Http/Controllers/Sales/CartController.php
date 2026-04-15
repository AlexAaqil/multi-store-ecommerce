<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\CartService;
use App\Models\Sales\CartItem;

class CartController extends Controller
{
    protected CartService $cart_service;

    public function __construct(CartService $cart_service)
    {
        $this->cart_service = $cart_service;
    }

    /**
     * Show cart page
     */
    public function index(Request $request)
    {
        $cart = $this->cart_service->getCart(
            Auth::user(),
            $request->session()->getId()
        );
        
        $cart->load(['items.product.images', 'items.variant', 'items.shop']);

        $items = $cart->items->map(function ($item) {
            $price = $item->variant 
                ? $item->product->price + $item->variant->price_adjustment
                : $item->product->price;
            
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_slug' => $item->product->slug,
                'product_image' => $item->product->primary_image_url,
                'variant_id' => $item->variant_id,
                'variant_name' => $item->variant ? json_encode($item->variant->attributes) : null,
                'shop_id' => $item->shop_id,
                'shop_name' => $item->shop->name,
                'quantity' => $item->quantity,
                'unit_price' => $price,
                'subtotal' => $item->quantity * $price,
                'stock' => $item->variant ? $item->variant->stock_qty : $item->product->stock_qty,
            ];
        });

        // Calculate totals
        $total = $items->sum('subtotal');
        $itemCount = $items->sum('quantity');

        if ($request->wantsJson()) {
            return response()->json([
                'items' => $items,
                'total' => $cart->total,
                'item_count' => $cart->item_count,
            ]);
        }
        
        return inertia('guest/sales/cart/Index', [
            'cart' => [
                'items' => $items,
                'total' => $total,
                'item_count' => $itemCount,
            ]
        ]);
    }

    /**
     * Add to cart (AJAX from Inertia)
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'sometimes|integer|min:1|max:999',
            'variant_id' => 'nullable|exists:product_variants,id'
        ]);

        try {
            $cart = $this->cart_service->getCart(
                Auth::user(),
                $request->session()->getId()
            );
            
            $cartItem = $this->cart_service->addItem(
                $cart,
                $request->product_id,
                $request->quantity ?? 1,
                $request->variant_id
            );
            
            // Refresh cart with relationships
            $cart->load(['items.product.images', 'items.variant', 'items.shop']);

            $items = $cart->items->map(function ($item) {
                $price = $item->variant 
                    ? $item->product->price + $item->variant->price_adjustment
                    : $item->product->price;
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_slug' => $item->product->slug,
                    'product_image' => $item->product->images->first()->name ?? null,
                    'variant_id' => $item->variant_id,
                    'variant_name' => $item->variant ? json_encode($item->variant->attributes) : null,
                    'shop_id' => $item->shop_id,
                    'shop_name' => $item->shop->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $price,
                    'subtotal' => $item->quantity * $price,
                    'stock' => $item->variant ? $item->variant->stock_qty : $item->product->stock_qty
                ];
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item added to cart',
                    'cart' => [
                        'items' => $items,
                        'total' => $cart->total,
                        'item_count' => $cart->item_count,
                    ]
                ]);
            }
            
            return redirect()
                ->back()
                ->with([
                    'message' => 'Item added to cart',
                    'type' => 'success'
                ]);
            
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }
        
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, CartItem $cartItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0|max:999'
        ]);

        // Verify ownership
        $cart = $this->cart_service->getCart(
            Auth::user(),
            $request->session()->getId()
        );
        
        if ($cartItem->cart_id !== $cart->id) {
            abort(403);
        }

        try {
            $this->cart_service->updateQuantity($cartItem, $request->quantity);
            return redirect()->back()->with('success', 'Cart updated');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove from cart
     */
    public function remove(Request $request, CartItem $cartItem)
    {
        // Verify ownership
        $cart = $this->cart_service->getCart(
            Auth::user(),
            $request->session()->getId()
        );
        
        if ($cartItem->cart_id !== $cart->id) {
            abort(403);
        }

        $this->cart_service->removeItem($cartItem);
        
        return redirect()->back()->with('success', 'Item removed from cart');
    }

    /**
     * Clear entire cart
     */
    public function clear(Request $request)
    {
        $cart = $this->cart_service->getCart(
            Auth::user(),
            $request->session()->getId()
        );
        
        $this->cart_service->clearCart($cart);
        
        return redirect()->back()->with('success', 'Cart cleared');
    }

    /**
     * Get cart summary (for AJAX requests)
     */
    public function summary(Request $request)
    {
        $cart = $this->cart_service->getCart(
            Auth::user(),
            $request->session()->getId()
        );
        
        $cart->load(['items.product.images', 'items.variant', 'items.shop']);
        
        $items = $cart->items->map(function ($item) {
            $price = $item->variant 
                ? $item->product->price + $item->variant->price_adjustment
                : $item->product->price;
            
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_slug' => $item->product->slug,
                'product_image' => $item->product->images->first()->name ?? null,
                'variant_id' => $item->variant_id,
                'variant_name' => $item->variant ? json_encode($item->variant->attributes) : null,
                'shop_id' => $item->shop_id,
                'shop_name' => $item->shop->name,
                'quantity' => $item->quantity,
                'unit_price' => $price,
                'subtotal' => $item->quantity * $price,
                'stock' => $item->variant ? $item->variant->stock_qty : $item->product->stock_qty,
            ];
        });
        
        // Calculate totals
        $total = $items->sum('subtotal');
        $itemCount = $items->sum('quantity');
        
        return response()->json([
            'items' => $items,
            'total' => $total,
            'item_count' => $itemCount,
        ]);
    }
}