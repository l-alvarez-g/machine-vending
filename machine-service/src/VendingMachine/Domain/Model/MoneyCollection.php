<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

final readonly class MoneyCollection
{
    /** @var Coin[] */
    private array $coins;

    public function __construct(Coin ...$coins)
    {
        $this->coins = array_values($coins);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function add(Coin $coin): self
    {
        return new self(...[...$this->coins, $coin]);
    }

    public function totalInCents(): int
    {
        $total = 0;
        foreach ($this->coins as $coin) {
            $total += $coin->amountInCents;
        }

        return $total;
    }

    /**
     * @return Coin[]
     */
    public function coins(): array
    {
        return $this->coins;
    }
}