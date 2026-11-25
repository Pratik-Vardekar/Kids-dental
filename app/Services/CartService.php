<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CartService
{
    public const SESSION_KEY = 'guest_cart';

    public function getCartCount(): int
    {
        if (Auth::check()) {
            return (int) Cart::where('user_id', Auth::id())->whereHas('product')->count();
        }

        return count($this->getGuestCart());
    }

    public function getCartProductIds(): array
    {
        if (Auth::check()) {
            return Cart::where('user_id', Auth::id())
                ->whereHas('product')
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return array_map(function ($key) {
            return (int) explode('_', (string) $key)[0];
        }, array_keys($this->getGuestCart()));
    }

    public function isInCart(int $productId, ?int $variantId = null): bool
    {
        if (Auth::check()) {
            $query = Cart::where('user_id', Auth::id())->where('product_id', $productId);
            if ($variantId) {
                $query->where('variant_id', $variantId);
            }
            return $query->exists();
        }

        $cartKey = $this->buildCartKey($productId, $variantId);
        return isset($this->getGuestCart()[$cartKey]);
    }

    public function getGuestCart(): array
    {
        return session(self::SESSION_KEY, []);
    }

    private function buildCartKey(int $productId, ?int $variantId = null): string
    {
        return $variantId ? "{$productId}_{$variantId}" : (string) $productId;
    }

    private function parseCartKey(string $key): array
    {
        if (str_contains($key, '_')) {
            [$prodId, $varId] = explode('_', $key, 2);
            return [(int) $prodId, (int) $varId];
        }
        return [(int) $key, null];
    }

    public function addItem(int $productId, int $qty = 1, ?int $variantId = null): int
    {
        $product = Product::findOrFail($productId);
        $qty = max(1, $qty);

        // Validate variant ownership if specified
        if ($variantId) {
            $variant = ProductVariant::where('product_id', $productId)->find($variantId);
            if (!$variant || !$variant->status) {
                throw new \InvalidArgumentException('The selected product variant is invalid or unavailable.');
            }
        } elseif ($product->isVariable() && $product->variants()->where('status', 1)->exists()) {
            // If variable product and no variant provided, attempt to pick the first active variant
            $variantId = $product->variants()->where('status', 1)->value('id');
        }

        if (Auth::check()) {
            $cartQuery = Cart::where('user_id', Auth::id())
                ->where('product_id', $productId);

            if ($variantId) {
                $cartQuery->where('variant_id', $variantId);
            } else {
                $cartQuery->whereNull('variant_id');
            }

            $cartItem = $cartQuery->first();

            if ($cartItem) {
                $cartItem->increment('quantity', $qty);
            } else {
                Cart::create([
                    'user_id'    => Auth::id(),
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity'   => $qty,
                ]);
            }

            return $this->getCartCount();
        }

        $guestCart = $this->getGuestCart();
        $cartKey = $this->buildCartKey($productId, $variantId);
        $guestCart[$cartKey] = ($guestCart[$cartKey] ?? 0) + $qty;
        session([self::SESSION_KEY => $guestCart]);

        return $this->getCartCount();
    }

    public function getCartItems(): Collection
    {
        if (Auth::check()) {
            return Cart::with(['product.gstSetting', 'variant'])
                ->where('user_id', Auth::id())
                ->whereHas('product')
                ->latest()
                ->get();
        }

        $guestCart = $this->getGuestCart();
        if (empty($guestCart)) {
            return collect();
        }

        $productIds = [];
        $variantIds = [];
        foreach (array_keys($guestCart) as $key) {
            [$pId, $vId] = $this->parseCartKey((string) $key);
            $productIds[] = $pId;
            if ($vId) {
                $variantIds[] = $vId;
            }
        }

        $products = Product::with('gstSetting')
            ->whereIn('id', array_unique($productIds))
            ->get()
            ->keyBy('id');

        $variants = !empty($variantIds)
            ? ProductVariant::whereIn('id', array_unique($variantIds))->get()->keyBy('id')
            : collect();

        return collect($guestCart)->map(function ($qty, $key) use ($products, $variants) {
            [$productId, $variantId] = $this->parseCartKey((string) $key);
            $product = $products->get($productId);
            if (!$product) {
                return null;
            }

            $variant = $variantId ? $variants->get($variantId) : null;

            return (object) [
                'id'         => 'guest_' . $key,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity'   => (int) $qty,
                'product'    => $product,
                'variant'    => $variant,
                'is_guest'   => true,
            ];
        })->filter()->values();
    }

    public function mergeGuestCartToUser(int $userId): void
    {
        $guestCart = $this->getGuestCart();
        if (empty($guestCart)) {
            return;
        }

        foreach ($guestCart as $key => $qty) {
            [$productId, $variantId] = $this->parseCartKey((string) $key);

            $cartQuery = Cart::where('user_id', $userId)
                ->where('product_id', $productId);

            if ($variantId) {
                $cartQuery->where('variant_id', $variantId);
            } else {
                $cartQuery->whereNull('variant_id');
            }

            $cartItem = $cartQuery->first();

            if ($cartItem) {
                $cartItem->increment('quantity', (int) $qty);
            } else {
                Cart::create([
                    'user_id'    => $userId,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity'   => (int) $qty,
                ]);
            }
        }

        session()->forget(self::SESSION_KEY);
    }

    public function updateGuestItem(int $productId, int $qty, ?int $variantId = null): void
    {
        $guestCart = $this->getGuestCart();
        $cartKey = $this->buildCartKey($productId, $variantId);

        if ($qty < 1) {
            unset($guestCart[$cartKey]);
        } else {
            $guestCart[$cartKey] = $qty;
        }

        session([self::SESSION_KEY => $guestCart]);
    }

    public function removeGuestItem(int $productId, ?int $variantId = null): void
    {
        $guestCart = $this->getGuestCart();
        $cartKey = $this->buildCartKey($productId, $variantId);
        unset($guestCart[$cartKey]);
        session([self::SESSION_KEY => $guestCart]);
    }
}
