<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InvalidCoinException;

final readonly class Coin
{
    public int $amountInCents;

    public function __construct(float $amount)
    {
        // A physical coin must have a positive value. 
        // Regional validity (e.g., 0.05, 0.25) is now enforced at the application/infrastructure boundary.
        if ($amount <= 0.0) {
            throw new InvalidCoinException(sprintf('Invalid coin value: %s', $amount));
        }

        // Safe conversion to cents avoiding float precision loss
        $this->amountInCents = (int) round($amount * 100);
    }

    public function amount(): float
    {
        return $this->amountInCents / 100;
    }
}