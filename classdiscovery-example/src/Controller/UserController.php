<?php

declare(strict_types=1);

namespace KaririCode\ClassDiscovery\Example\Controller;

use KaririCode\ClassDiscovery\Example\Attribute\Route;

/**
 * HTTP controller for user resource operations.
 *
 * @since 1.0.0
 */
#[Route('/users')]
final class UserController
{
    #[Route('/users/{id}', 'GET')]
    public function show(int $id): string
    {
        return "User #{$id}";
    }

    #[Route('/users', 'POST')]
    public function store(): string
    {
        return 'User created';
    }

    #[Route('/users/{id}', 'DELETE')]
    public function destroy(int $id): string
    {
        return "User #{$id} deleted";
    }
}
