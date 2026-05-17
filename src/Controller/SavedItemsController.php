<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\SavedItemService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SavedItemsController extends AbstractController
{
    #[Route('/save/{id<\d+>}', name: 'app_saved_toggle', methods: ['POST'])]
    public function toggle(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        SavedItemService $savedItemService,
    ): JsonResponse {
        $product = $productRepository->findVisibleById($id);
        if (!$product instanceof Product) {
            return $this->json(['saved' => false, 'message' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isCsrfTokenValid('save_'.$id, (string) $request->request->get('_token'))) {
            return $this->json(['saved' => false, 'message' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $saved = $savedItemService->toggle($product);

        return $this->json([
            'saved' => $saved,
            'savedItemsCount' => $savedItemService->count(),
        ]);
    }

    #[Route('/saved', name: 'app_saved_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        SavedItemService $savedItemService,
        CartService $cartService,
    ): Response {
        $savedItems = $savedItemService->listForCurrentUser();
        $savedProductIds = [];
        foreach ($savedItems as $savedItem) {
            $product = $savedItem->getProduct();
            if ($product !== null && $product->getId() !== null) {
                $savedProductIds[] = $product->getId();
            }
        }

        return $this->render('saved/index.html.twig', [
            'savedItems' => $savedItems,
            'savedProductIds' => $savedProductIds,
            'cartItemCount' => $cartService->itemCount(),
            'savedItemsCount' => $savedItemService->count(),
            'search' => null,
            'sort' => null,
            'selectedCategories' => [],
        ]);
    }
}
