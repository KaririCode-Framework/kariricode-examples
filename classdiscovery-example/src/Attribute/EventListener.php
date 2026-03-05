<?php

declare(strict_types=1);

namespace KaririCode\ClassDiscovery\Example\Attribute;

/**
 * Marks a class as an event listener for auto-registration in the event dispatcher.
 *
 * Pure Data Parameter Object — immutable by construction.
 *
 * @since 1.0.0
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class EventListener
{
    public function __construct(
        public readonly string $event,
        public readonly int $priority = 0,
    ) {
    }
}
