<?php

declare(strict_types=1);

namespace KaririCode\ClassDiscovery\Example\Listener;

use KaririCode\ClassDiscovery\Example\Attribute\EventListener;

/**
 * Writes an audit log entry when a new user registers.
 *
 * Discovered automatically via #[EventListener] attribute scan.
 *
 * @since 1.0.0
 */
#[EventListener(event: 'user.registered', priority: 5)]
final class AuditLogListener
{
    /** Handles the dispatched event. */
    public function handle(object $event): void
    {
        // Production implementation would append a record to the audit log store.
    }
}
