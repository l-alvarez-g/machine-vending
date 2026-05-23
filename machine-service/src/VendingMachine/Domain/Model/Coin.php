<?php
declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InvalidCoinException;

final readonly class Coin
{
    public int $amountInCents;

    /**
     * Valid denominations in cents: 0.05, 0.10, 0.25, 1.00
     */
    private const array VALID_COINS_IN_CENTS = [5, 10, 25, 100];

    public function __construct(float $amount)
    {
        // Safe conversion to cents avoiding float precision loss
        $cents = (int) round($amount * 100);

        if (!in_array($cents, self::VALID_COINS_IN_CENTS, true)) {
            throw new InvalidCoinException(sprintf('Invalid coin value: %s', $amount));
        }

        $this->amountInCents = $cents;
    }

    public function amount(): float
    {
        return $this->amountInCents / 100;
    }
}