<?php

declare(strict_types=1);

namespace KaririCode\ClassDiscovery\Example\Attribute;

/**
 * Marks a class as a DI container service for auto-registration.
 *
 * Pure Data Parameter Object — immutable by construction.
 *
 * @since 1.0.0
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Service
{
    public function __construct(
        public readonly string $id = '',
        public readonly bool $singleton = true,
    ) {
    }
}
