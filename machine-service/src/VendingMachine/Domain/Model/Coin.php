<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InvalidCoinException;

final readonly class Coin
{
    /**
     * @throws InvalidCoinException
     */
    public function __construct(
        private int $amountInCents
    ) {
        if ($this->amountInCents <= 0) {
           throw InvalidCoinException::forInvalidDenomination($this->amountInCents);
        }
    }

    /**
     * Creates a Coin from a float value safely.
     * * @throws InvalidCoinException
     */
    public static function fromFloat(float $amount): self
    {
        if ($amount <= 0.0) {
            throw InvalidCoinException::forInvalidDenomination((int) round($amount * 100));
        }

        return new self((int) round($amount * 100));
    }

    /**
     * Accessor method for the internal value in cents.
     */
    public function amountInCents(): int
    {
        return $this->amountInCents;
    }

    public function amount(): float
    {
        return $this->amountInCents / 100;
    }

    /**
     * Value Objects must be structurally comparable.
     */
    public function equals(self $other): bool
    {
        return $this->amountInCents === $other->amountInCents();
    }
}