<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\SavedItemService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PaymentController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/checkout', name: 'app_payment_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        CartService $cartService,
        ProductRepository $productRepository,
        #[Autowire('%env(string:STRIPE_SECRET_KEY)%')] string $stripeSecretKey,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('checkout', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $details = $cartService->details($productRepository);
        if ($details['items'] === []) {
            $this->addFlash('warning', 'Your cart is empty.');

            return $this->redirectToRoute('app_cart_index');
        }

        if ($stripeSecretKey === '') {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }

        Stripe::setApiKey($stripeSecretKey);

        $lineItems = [];
        foreach ($details['items'] as $item) {
            $product = $item['product'];
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => (int) round(((float) $product->getPrice()) * 100),
                    'product_data' => [
                        'name' => (string) $product->getName(),
                        'description' => mb_substr((string) $product->getDescription(), 0, 200),
                    ],
                ],
                'quantity' => $item['quantity'],
            ];
        }

        $session = Session::create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'success_url' => $this->generateUrl('app_payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl('app_cart_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/payment/success', name: 'app_payment_success', methods: ['GET'])]
    public function success(
        Request $request,
        CartService $cartService,
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
        SavedItemService $savedItemService,
        #[Autowire('%env(string:STRIPE_SECRET_KEY)%')] string $stripeSecretKey,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $checkoutSession = null;
        $sessionId = (string) $request->query->get('session_id', '');
        if ($sessionId !== '' && $stripeSecretKey !== '') {
            Stripe::setApiKey($stripeSecretKey);
            try {
                $checkoutSession = Session::retrieve($sessionId);
            } catch (ApiErrorException) {
                $checkoutSession = null;
            }
        }

        $details = $cartService->details($productRepository);
        if ($details['items'] !== [] && ($sessionId === '' || $orderRepository->findOneBy(['stripeSessionId' => $sessionId]) === null)) {
            $order = (new Order())
                ->setUser($user)
                ->setOrderNumber('ORD-'.strtoupper(bin2hex(random_bytes(4))))
                ->setTotal((float) $details['subtotal'])
                ->setStatus('completed')
                ->setStripeSessionId($sessionId !== '' ? $sessionId : null);

            foreach ($details['items'] as $item) {
                $product = $item['product'];
                $orderItem = (new OrderItem())
                    ->setOrder($order)
                    ->setProduct($product)
                    ->setProductName((string) $product->getName())
                    ->setUnitPrice((float) $product->getPrice())
                    ->setQuantity($item['quantity']);
                $order->addItem($orderItem);
                $entityManager->persist($orderItem);
            }

            $entityManager->persist($order);
            $entityManager->flush();
        }

        $cartService->clear();

        return $this->render('payment/success.html.twig', [
            'checkoutSession' => $checkoutSession,
            'cartItemCount' => 0,
            'savedItemsCount' => $savedItemService->count(),
            'search' => null,
            'sort' => null,
            'selectedCategories' => [],
        ]);
    }
}
