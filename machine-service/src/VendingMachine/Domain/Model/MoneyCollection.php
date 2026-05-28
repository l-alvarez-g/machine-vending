<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

final readonly class MoneyCollection
{
    /** * @var list<Coin> 
     */
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
        return array_reduce(
            $this->coins,
            static fn (int $total, Coin $coin): int => $total + $coin->amountInCents(),
            0
        );
    }

    /**
     * @return list<Coin>
     */
    public function coins(): array
    {
        return $this->coins;
    }
}