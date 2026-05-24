<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InvalidCoinException;

final readonly class AcceptedCoinsPolicy
{
    /**
     * @param array<int, int> $validCoinsInCents
     */
    public function __construct(
        private array $validCoinsInCents
    ) {
    }

    /**
     * Evaluates if the given coin satisfies the acceptance policy.
     */
    public function isSatisfiedBy(Coin $coin): bool
    {
        return in_array($coin->amountInCents, $this->validCoinsInCents, true);
    }

    /**
     * Asserts that the coin is accepted, throwing a domain exception otherwise.
     * 
     * @throws InvalidCoinException
     */
    public function assertIsSatisfiedBy(Coin $coin): void
    {
        if (!$this->isSatisfiedBy($coin)) {
            throw new InvalidCoinException(
                sprintf('The machine does not accept the coin value: %s', $coin->amount())
            );
        }
    }
}