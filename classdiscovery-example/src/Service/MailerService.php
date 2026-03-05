<?php

declare(strict_types=1);

namespace KaririCode\ClassDiscovery\Example\Service;

use KaririCode\ClassDiscovery\Example\Attribute\Service;

/**
 * Handles outbound email delivery.
 *
 * Discovered automatically via #[Service] attribute scan.
 *
 * @since 1.0.0
 */
#[Service(id: 'mailer', singleton: true)]
final class MailerService
{
    /**
     * Sends an email to the given recipient.
     *
     * @param non-empty-string $to      Recipient email address.
     * @param non-empty-string $subject Email subject line.
     */
    public function send(string $to, string $subject): bool
    {
        // Production implementation would delegate to an SMTP adapter.
        return true;
    }
}
