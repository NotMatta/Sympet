<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private const CART_KEY = 'cart.items';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return array<int, int>
     */
    public function all(): array
    {
        return $this->getSession()->get(self::CART_KEY, []);
    }

    public function itemCount(): int
    {
        return array_sum($this->all());
    }

    public function add(int $productId, int $quantity, int $maxStock): void
    {
        $cart = $this->all();
        $current = $cart[$productId] ?? 0;
        $cart[$productId] = min($current + $quantity, $maxStock);
        $this->save($cart);
    }

    public function update(int $productId, int $quantity, int $maxStock): void
    {
        $cart = $this->all();
        if ($quantity <= 0) {
            unset($cart[$productId]);
            $this->save($cart);

            return;
        }

        $cart[$productId] = min($quantity, $maxStock);
        $this->save($cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->all();
        unset($cart[$productId]);
        $this->save($cart);
    }

    public function clear(): void
    {
        $this->getSession()->remove(self::CART_KEY);
    }

    /**
     * @return array{
     *     items: array<int, array{product: \App\Entity\Product, quantity: int, lineTotal: float}>,
     *     subtotal: float
     * }
     */
    public function details(ProductRepository $productRepository): array
    {
        $cart = $this->all();
        if ($cart === []) {
            return ['items' => [], 'subtotal' => 0.0];
        }

        $products = $productRepository->findVisibleByIds(array_map('intval', array_keys($cart)));
        $productsById = [];
        foreach ($products as $product) {
            $id = $product->getId();
            if ($id !== null) {
                $productsById[$id] = $product;
            }
        }

        $items = [];
        $subtotal = 0.0;
        foreach ($cart as $productId => $quantity) {
            if (!isset($productsById[$productId])) {
                unset($cart[$productId]);
                continue;
            }

            $product = $productsById[$productId];
            $inStock = max(0, $product->getQuantity() ?? 0);
            if ($inStock === 0) {
                unset($cart[$productId]);
                continue;
            }

            $safeQuantity = min($quantity, $inStock);
            $lineTotal = ((float) $product->getPrice()) * $safeQuantity;
            $subtotal += $lineTotal;
            $items[] = [
                'product' => $product,
                'quantity' => $safeQuantity,
                'lineTotal' => $lineTotal,
            ];

            $cart[$productId] = $safeQuantity;
        }

        $this->save($cart);

        return ['items' => $items, 'subtotal' => $subtotal];
    }

    private function save(array $items): void
    {
        $this->getSession()->set(self::CART_KEY, $items);
    }

    private function getSession(): SessionInterface
    {
        $session = $this->requestStack->getSession();
        if ($session === null) {
            throw new \RuntimeException('Session is not available.');
        }

        return $session;
    }
}
