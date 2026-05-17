<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
final class CartController extends AbstractController
{
    #[Route('', name: 'app_cart_index', methods: ['GET'])]
    public function index(
        CartService $cartService,
        ProductRepository $productRepository,
    ): Response {
        $details = $cartService->details($productRepository);

        return $this->render('cart/index.html.twig', [
            'items' => $details['items'],
            'subtotal' => $details['subtotal'],
            'cartItemCount' => $cartService->itemCount(),
            'search' => null,
            'sort' => null,
            'selectedCategories' => [],
        ]);
    }

    #[Route('/add', name: 'app_cart_add', methods: ['POST'])]
    public function add(
        Request $request,
        ProductRepository $productRepository,
        CartService $cartService,
    ): JsonResponse|RedirectResponse {
        $productId = $request->request->getInt('productId');
        $quantity = max(1, min(99, $request->request->getInt('quantity', 1)));
        $token = (string) $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('cart_add_'.$productId, $token)) {
            return $this->cartErrorResponse($request, 'Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $product = $productRepository->findVisibleById($productId);
        if ($product === null) {
            return $this->cartErrorResponse($request, 'Product not found.', Response::HTTP_NOT_FOUND);
        }

        $stock = max(0, $product->getQuantity() ?? 0);
        if ($stock === 0) {
            return $this->cartErrorResponse($request, 'This product is out of stock.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $currentQuantity = $cartService->all()[$productId] ?? 0;
        if ($currentQuantity + $quantity > $stock) {
            return $this->cartErrorResponse($request, 'Requested quantity exceeds stock.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cartService->add($productId, $quantity, $stock);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'message' => 'Added to cart.',
                'cartCount' => $cartService->itemCount(),
            ]);
        }

        $this->addFlash('success', 'Product added to cart.');

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_shop'));
    }

    #[Route('/update/{id<\d+>}', name: 'app_cart_update', methods: ['POST'])]
    public function update(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        CartService $cartService,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('cart_update_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $quantity = max(0, min(99, $request->request->getInt('quantity', 1)));
        $product = $productRepository->findVisibleById($id);
        if ($product === null) {
            $cartService->remove($id);
            $this->addFlash('warning', 'Product no longer available and was removed from cart.');

            return $this->redirectToRoute('app_cart_index');
        }

        $stock = max(0, $product->getQuantity() ?? 0);
        if ($stock === 0) {
            $cartService->remove($id);
            $this->addFlash('warning', 'Out of stock item removed from cart.');

            return $this->redirectToRoute('app_cart_index');
        }

        if ($quantity > $stock) {
            $quantity = $stock;
            $this->addFlash('warning', 'Quantity was adjusted to available stock.');
        }

        $cartService->update($id, $quantity, $stock);

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{id<\d+>}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(
        int $id,
        Request $request,
        CartService $cartService,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('cart_remove_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $cartService->remove($id);

        return $this->redirectToRoute('app_cart_index');
    }

    private function cartErrorResponse(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'message' => $message], $status);
        }

        $this->addFlash('error', $message);

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_shop'));
    }
}
