<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/shop', name: 'app_shop')]
    public function index(): Response
    {

        $products = [
            ['name' => 'Product 1', 'price' => 10.99, "description" => 'Description of Product 1', "image" => 'https://cdn11.bigcommerce.com/s-asivtkjxr8/images/stencil/1280x1280/products/3028/14786/t1agmszxedxmmlflg5mn__40653.1651035119.jpg?c=1'],
            ['name' => 'Product 2', 'price' => 19.99, "description" => 'Description of Product 2', "image" => 'https://cdn11.bigcommerce.com/s-asivtkjxr8/images/stencil/1280x1280/products/3028/14786/t1agmszxedxmmlflg5mn__40653.1651035119.jpg?c=1'],
            ['name' => 'Product 3', 'price' => 5.99, "description" => 'Description of Product 3', "image" => 'https://cdn11.bigcommerce.com/s-asivtkjxr8/images/stencil/1280x1280/products/3028/14786/t1agmszxedxmmlflg5mn__40653.1651035119.jpg?c=1'],
            ['name' => 'Product 4', 'price' => 8.99, "description" => 'Description of Product 4', "image" => 'https://cdn11.bigcommerce.com/s-asivtkjxr8/images/stencil/1280x1280/products/3028/14786/t1agmszxedxmmlflg5mn__40653.1651035119.jpg?c=1'],
        ];

        return $this->render('product/index.html.twig', [
            'controller_name' => 'ProductController',
            'products' => $products,
        ]);
    }
}
