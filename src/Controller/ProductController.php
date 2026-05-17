<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\SavedItemService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/shop', name: 'app_shop')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        CartService $cartService,
        SavedItemService $savedItemService,
    ): Response
    {
        $selectedCategoryIds = array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $id): int => (int) $id,
                (array) $request->query->all('categories'),
            ),
            static fn (int $id): bool => $id > 0,
        )));

        $sort = (string) $request->query->get('sort', 'newest');
        $allowedSorts = ['newest', 'price_asc', 'price_desc', 'name_asc'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'newest';
        }

        $search = trim((string) $request->query->get('search', ''));
        if ($search === '') {
            $search = null;
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;

        $result = $productRepository->findShopProducts(
            $selectedCategoryIds,
            $search,
            $sort,
            $page,
            $limit,
        );

        $totalPages = max(1, (int) ceil($result['totalFiltered'] / $limit));
        if ($page > $totalPages && $result['totalFiltered'] > 0) {
            $page = $totalPages;
            $result = $productRepository->findShopProducts(
                $selectedCategoryIds,
                $search,
                $sort,
                $page,
                $limit,
            );
        }

        $categories = $categoryRepository->findBy([], ['name' => 'ASC']);

        $savedProductIds = array_map(
            static fn ($savedItem): int => (int) $savedItem->getProduct()?->getId(),
            array_filter($savedItemService->listForCurrentUser(), static fn ($savedItem): bool => $savedItem->getProduct() !== null),
        );

        return $this->render('product/index.html.twig', [
            'products' => $result['products'],
            'categories' => $categories,
            'selectedCategoryIds' => $selectedCategoryIds,
            'search' => $search,
            'sort' => $sort,
            'sortOptions' => [
                'newest' => 'Newest first',
                'price_asc' => 'Price: low to high',
                'price_desc' => 'Price: high to low',
                'name_asc' => 'Name: A-Z',
            ],
            'totalFiltered' => $result['totalFiltered'],
            'totalVisible' => $result['totalVisible'],
            'page' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit,
            'cartItemCount' => $cartService->itemCount(),
            'savedItemsCount' => $savedItemService->count(),
            'savedProductIds' => $savedProductIds,
        ]);
    }

    #[Route('/product/{id<\d+>}', name: 'app_product_show', methods: ['GET'])]
    public function show(
        int $id,
        ProductRepository $productRepository,
        CartService $cartService,
        SavedItemService $savedItemService,
    ): Response {
        $product = $productRepository->findVisibleById($id);
        if ($product === null) {
            throw $this->createNotFoundException('Product not found.');
        }

        $relatedProducts = $productRepository->findRelatedProducts($product);

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'cartItemCount' => $cartService->itemCount(),
            'savedItemsCount' => $savedItemService->count(),
            'savedProductIds' => array_map(
                static fn ($savedItem): int => (int) $savedItem->getProduct()?->getId(),
                array_filter($savedItemService->listForCurrentUser(), static fn ($savedItem): bool => $savedItem->getProduct() !== null),
            ),
            'search' => null,
            'sort' => null,
            'selectedCategories' => [],
        ]);
    }
}
