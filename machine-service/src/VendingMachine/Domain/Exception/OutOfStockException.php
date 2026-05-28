<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Exception;

use DomainException;

final class OutOfStockException extends DomainException
{
    /**
     * Creates an exception for when a requested product is not available in the inventory.
     */
    public static function forProduct(string $productName): self
    {
        return new self(
            sprintf('The requested product "%s" is currently out of stock.', $productName)
        );
    }
}