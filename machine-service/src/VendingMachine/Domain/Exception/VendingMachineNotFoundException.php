<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Exception;

use DomainException;

final class VendingMachineNotFoundException extends DomainException
{
    /**
     * Creates an exception specifically for a missing aggregate ID.
     */
    public static function withId(string $id): self
    {
        return new self(
            sprintf('Vending machine with ID "%s" could not be found.', $id)
        );
    }
}