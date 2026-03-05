<?php

declare(strict_types=1);

namespace KaririCode\ClassDiscovery\Example\Service;

use KaririCode\ClassDiscovery\Example\Attribute\Service;
use KaririCode\ClassDiscovery\Example\Contract\Cacheable;
use KaririCode\ClassDiscovery\Example\Contract\Loggable;

/**
 * Full-featured service implementing Loggable + Cacheable.
 *
 * Used to demonstrate CompositeFilter AND-logic (must implement both interfaces)
 * and InterfaceFilter for each interface separately.
 *
 * @since 1.0.0
 */
#[Service(id: 'cache', singleton: true)]
final class CacheService implements Loggable, Cacheable
{
    public function log(string $message): void
    {
        // no-op in example context
    }

    public function getCacheKey(): string
    {
        return 'cache_service';
    }

    public function getTtl(): int
    {
        return 3600;
    }
}
