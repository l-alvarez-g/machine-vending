<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Exception;

use DomainException;

final class InsufficientFundsException extends DomainException
{
    /**
     * Creates an exception detailing the financial gap in the transaction.
     */
    public static function forTransaction(int $insertedInCents, int $requiredInCents): self
    {
        return new self(
            sprintf(
                'Not enough money inserted. Inserted: %d cents, Required: %d cents.',
                $insertedInCents,
                $requiredInCents
            )
        );
    }
}