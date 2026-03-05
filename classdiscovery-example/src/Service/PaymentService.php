<?php

declare(strict_types=1);

namespace KaririCode\ClassDiscovery\Example\Service;

use KaririCode\ClassDiscovery\Example\Attribute\Service;

/**
 * Handles payment processing operations.
 *
 * Discovered automatically via #[Service] attribute scan.
 *
 * @since 1.0.0
 */
#[Service(id: 'payment', singleton: false)]
final class PaymentService
{
    /**
     * Charges the given amount in the specified currency.
     *
     * @param positive-int|float $amount   Amount to charge.
     * @param non-empty-string   $currency ISO 4217 currency code.
     */
    public function charge(float $amount, string $currency = 'BRL'): bool
    {
        // Production implementation would delegate to a payment gateway adapter.
        return true;
    }
}
