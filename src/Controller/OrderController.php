<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Service\CartService;
use App\Service\SavedItemService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    #[Route('/orders', name: 'app_orders', methods: ['GET'])]
    public function index(
        OrderRepository $orderRepository,
        CartService $cartService,
        SavedItemService $savedItemService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $orders = $orderRepository->findByUser($user);

        return $this->render('settings/orders.html.twig', [
            'orders' => $orders,
            'cartItemCount' => $cartService->itemCount(),
            'savedItemsCount' => $savedItemService->count(),
            'search' => null,
            'sort' => null,
            'selectedCategories' => [],
        ]);
    }

    #[Route('/order/{id<\d+>}', name: 'app_order_detail', methods: ['GET'])]
    public function show(
        Order $order,
        CartService $cartService,
        SavedItemService $savedItemService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($order->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You cannot view this order.');
        }

        return $this->render('settings/order_detail.html.twig', [
            'order' => $order,
            'cartItemCount' => $cartService->itemCount(),
            'savedItemsCount' => $savedItemService->count(),
            'search' => null,
            'sort' => null,
            'selectedCategories' => [],
        ]);
    }
}
