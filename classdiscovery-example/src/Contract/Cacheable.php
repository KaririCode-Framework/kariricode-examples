<?php

declare(strict_types=1);

namespace KaririCode\ClassDiscovery\Example\Contract;

/**
 * Marks a class as a cacheable result producer.
 *
 * Used in filter examples alongside Loggable to demonstrate
 * CompositeFilter (AND/OR) with two interfaces.
 *
 * @since 1.0.0
 */
interface Cacheable
{
    public function getCacheKey(): string;

    public function getTtl(): int;
}
