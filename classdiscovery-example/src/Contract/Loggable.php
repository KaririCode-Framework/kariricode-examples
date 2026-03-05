<?php

declare(strict_types=1);

namespace KaririCode\ClassDiscovery\Example\Contract;

/**
 * Marks a class as producing log entries.
 *
 * Used in filter examples to demonstrate InterfaceFilter.
 *
 * @since 1.0.0
 */
interface Loggable
{
    public function log(string $message): void;
}
