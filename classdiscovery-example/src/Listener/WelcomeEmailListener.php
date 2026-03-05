<?php

declare(strict_types=1);

namespace KaririCode\ClassDiscovery\Example\Listener;

use KaririCode\ClassDiscovery\Example\Attribute\EventListener;

/**
 * Sends a welcome email when a new user registers.
 *
 * Discovered automatically via #[EventListener] attribute scan.
 *
 * @since 1.0.0
 */
#[EventListener(event: 'user.registered', priority: 10)]
final class WelcomeEmailListener
{
    /** Handles the dispatched event. */
    public function handle(object $event): void
    {
        // Production implementation would dispatch a welcome email job.
    }
}
