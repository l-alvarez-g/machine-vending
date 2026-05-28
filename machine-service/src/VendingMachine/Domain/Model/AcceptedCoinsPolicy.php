<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InvalidCoinException;
use InvalidArgumentException;

final readonly class AcceptedCoinsPolicy
{
    /**
     * @param array<int, int> $validCoinsInCents
     * @throws InvalidArgumentException
     */
    public function __construct(
        private array $validCoinsInCents
    ) {
        $this->assertValidConfiguration($validCoinsInCents);
    }

    /**
     * Evaluates if the given coin satisfies the acceptance policy.
     */
    public function isSatisfiedBy(Coin $coin): bool
    {
        // Ahora consumimos el método de acceso estructurado
        return in_array($coin->amountInCents(), $this->validCoinsInCents, true);
    }

    /**
     * Asserts that the coin is accepted, throwing a domain exception otherwise.
     * * @throws InvalidCoinException
     */
    public function assertIsSatisfiedBy(Coin $coin): void
    {
        if (!$this->isSatisfiedBy($coin)) {
            throw InvalidCoinException::forUnacceptedCoin($coin->amountInCents());
        }
    }

    /**
     * @param array<int, int> $validCoins
     * @throws InvalidArgumentException
     */
    private function assertValidConfiguration(array $validCoins): void
    {
        if ($validCoins === []) {
            throw new InvalidArgumentException('The accepted coins policy must contain at least one valid coin denomination.');
        }

        foreach ($validCoins as $coinValue) {
            if ($coinValue <= 0) {
                throw new InvalidArgumentException(
                    sprintf('Invalid coin denomination: %d. Must be strictly positive.', $coinValue)
                );
            }
        }
    }
}