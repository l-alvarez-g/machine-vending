<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Exception;

use DomainException;

final class InvalidProductException extends DomainException
{
    /**
     * Creates an exception for when a product is instantiated with an empty name.
     */
    public static function forEmptyName(): self
    {
        return new self('Product name cannot be empty.');
    }

    /**
     * Creates an exception for when a product is instantiated with a non-positive price.
     */
    public static function forInvalidPrice(int $priceInCents): self
    {
        return new self(
            sprintf('Product price must be greater than zero, got: %d cents.', $priceInCents)
        );
    }
}