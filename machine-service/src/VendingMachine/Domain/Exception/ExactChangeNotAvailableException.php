<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Exception;

use DomainException;

final class ExactChangeNotAvailableException extends DomainException
{
    /**
     * Creates an exception providing context about the failing amount.
     */
    public static function forAmount(int $pendingChangeInCents): self
    {
        return new self(
            sprintf(
                'Machine does not have exact change available to return the remaining %d cents.',
                $pendingChangeInCents
            )
        );
    }
}