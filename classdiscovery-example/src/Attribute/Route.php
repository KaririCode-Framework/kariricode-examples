<?php

declare(strict_types=1);

namespace KaririCode\ClassDiscovery\Example\Attribute;

/**
 * Marks a controller class or method as an HTTP route.
 *
 * Pure Data Parameter Object — immutable by construction.
 *
 * @since 1.0.0
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class Route
{
    public function __construct(
        public readonly string $path,
        public readonly string $method = 'GET',
    ) {
    }
}
