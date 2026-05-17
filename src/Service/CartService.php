<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private const SESSION_CART_TOKEN_KEY = 'cart.session_token';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly CartRepository $cartRepository,
        private readonly CartItemRepository $cartItemRepository,
    ) {
    }

    public function itemCount(): int
    {
        $cart = $this->getCurrentCart(false);
        if ($cart === null) {
            return 0;
        }

        $count = 0;
        foreach ($cart->getItems() as $item) {
            $count += $item->getQuantity();
        }

        return $count;
    }

    public function add(Product $product, int $quantity): void
    {
        $cart = $this->getCurrentCart(true);
        $item = $this->cartItemRepository->findOneByCartAndProduct($cart, $product);
        $currentQuantity = $item?->getQuantity() ?? 0;
        if ($item === null) {
            $item = (new CartItem())
                ->setCart($cart)
                ->setProduct($product)
                ->setQuantity(0);
        }

        $newQuantity = $currentQuantity + $quantity;
        $item->setQuantity(min($newQuantity, max(0, $product->getQuantity() ?? 0)));
        $cart->touch();

        $this->entityManager->persist($cart);
        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    public function update(Product $product, int $quantity): void
    {
        $cart = $this->getCurrentCart(false);
        if ($cart === null) {
            return;
        }

        $item = $this->cartItemRepository->findOneByCartAndProduct($cart, $product);
        if ($item === null) {
            return;
        }

        if ($quantity <= 0) {
            $this->entityManager->remove($item);
        } else {
            $item->setQuantity(min($quantity, max(0, $product->getQuantity() ?? 0)));
            $this->entityManager->persist($item);
        }

        $cart->touch();
        $this->entityManager->persist($cart);
        $this->entityManager->flush();
    }

    public function remove(Product $product): void
    {
        $cart = $this->getCurrentCart(false);
        if ($cart === null) {
            return;
        }

        $item = $this->cartItemRepository->findOneByCartAndProduct($cart, $product);
        if ($item !== null) {
            $this->entityManager->remove($item);
            $cart->touch();
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }
    }

    public function clear(): void
    {
        $cart = $this->getCurrentCart(false);
        if ($cart === null) {
            return;
        }

        foreach ($cart->getItems() as $item) {
            $this->entityManager->remove($item);
        }
        $cart->touch();
        $this->entityManager->persist($cart);
        $this->entityManager->flush();
    }

    /**
     * @return array{
     *     items: array<int, array{product: Product, quantity: int, lineTotal: float}>,
     *     subtotal: float
     * }
     */
    public function details(ProductRepository $productRepository): array
    {
        $cart = $this->getCurrentCart(false);
        if ($cart === null) {
            return ['items' => [], 'subtotal' => 0.0];
        }

        $items = [];
        $subtotal = 0.0;

        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();
            if ($product === null || $productRepository->findVisibleById((int) $product->getId()) === null) {
                $this->entityManager->remove($item);
                continue;
            }

            $stock = max(0, $product->getQuantity() ?? 0);
            if ($stock === 0) {
                $this->entityManager->remove($item);
                continue;
            }

            $safeQuantity = min($item->getQuantity(), $stock);
            if ($safeQuantity !== $item->getQuantity()) {
                $item->setQuantity($safeQuantity);
                $this->entityManager->persist($item);
            }

            $lineTotal = ((float) $product->getPrice()) * $safeQuantity;
            $subtotal += $lineTotal;
            $items[] = [
                'product' => $product,
                'quantity' => $safeQuantity,
                'lineTotal' => $lineTotal,
            ];
        }

        $this->entityManager->flush();

        return ['items' => $items, 'subtotal' => $subtotal];
    }

    public function mergeGuestCartIntoUser(User $user): void
    {
        $guestCart = $this->cartRepository->findOneBySessionToken($this->getSessionToken());
        $userCart = $this->cartRepository->findOneByUser($user);

        if ($guestCart === null) {
            return;
        }

        if ($userCart === null) {
            $guestCart->setUser($user);
            $guestCart->setSessionToken(null);
            $guestCart->touch();
            $this->entityManager->persist($guestCart);
            $this->entityManager->flush();

            return;
        }

        foreach ($guestCart->getItems() as $guestItem) {
            $product = $guestItem->getProduct();
            if ($product === null) {
                continue;
            }

            $existing = $this->cartItemRepository->findOneByCartAndProduct($userCart, $product);
            if ($existing !== null) {
                $existing->setQuantity($existing->getQuantity() + $guestItem->getQuantity());
                $this->entityManager->persist($existing);
            } else {
                $newItem = (new CartItem())
                    ->setCart($userCart)
                    ->setProduct($product)
                    ->setQuantity($guestItem->getQuantity());
                $this->entityManager->persist($newItem);
            }
        }

        foreach ($guestCart->getItems() as $guestItem) {
            $this->entityManager->remove($guestItem);
        }
        $this->entityManager->remove($guestCart);
        $userCart->touch();
        $this->entityManager->persist($userCart);
        $this->entityManager->flush();
    }

    public function getCurrentCart(bool $createIfMissing = true): ?Cart
    {
        $user = $this->getUser();
        if ($user !== null) {
            $cart = $this->cartRepository->findOneByUser($user);
            if ($cart === null && $createIfMissing) {
                $cart = (new Cart())->setUser($user);
                $this->entityManager->persist($cart);
                $this->entityManager->flush();
            }

            return $cart;
        }

        $token = $this->getSessionToken();
        $cart = $this->cartRepository->findOneBySessionToken($token);
        if ($cart === null && $createIfMissing) {
            $cart = (new Cart())->setSessionToken($token);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }

        return $cart;
    }

    private function getSessionToken(): string
    {
        $session = $this->getSession();
        $token = $session->get(self::SESSION_CART_TOKEN_KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(16));
            $session->set(self::SESSION_CART_TOKEN_KEY, $token);
        }

        return $token;
    }

    private function getUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
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
