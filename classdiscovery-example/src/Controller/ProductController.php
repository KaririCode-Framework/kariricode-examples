<?php

declare(strict_types=1);

namespace KaririCode\ClassDiscovery\Example\Controller;

use KaririCode\ClassDiscovery\Example\Attribute\Route;

/**
 * HTTP controller for product resource operations.
 *
 * @since 1.0.0
 */
#[Route('/products')]
final class ProductController
{
    #[Route('/products', 'GET')]
    public function index(): string
    {
        return 'Product list';
    }

    #[Route('/products/{id}', 'GET')]
    public function show(int $id): string
    {
        return "Product #{$id}";
    }
}
