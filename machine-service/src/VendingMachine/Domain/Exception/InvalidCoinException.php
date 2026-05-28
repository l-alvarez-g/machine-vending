<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Exception;

use DomainException;

final class InvalidCoinException extends DomainException
{
    /**
     * Creates an exception for when a coin has an impossible physical/structural value.
     */
    public static function forInvalidDenomination(int $amountInCents): self
    {
        return new self(
            sprintf('Invalid coin denomination. Amount must be strictly positive, got: %d cents.', $amountInCents)
        );
    }

    /**
     * Creates an exception for when a structurally valid coin is rejected by the machine's policy.
     */
    public static function forUnacceptedCoin(int $amountInCents): self
    {
        return new self(
            sprintf('The machine does not accept the coin denomination of %d cents.', $amountInCents)
        );
    }
}