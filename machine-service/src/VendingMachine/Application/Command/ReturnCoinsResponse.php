<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

final readonly class ReturnCoinsResponse
{
    /**
     * @param array<int, float> $returnedCoins List of individual coin values (e.g. [0.25, 0.10])
     * @param float $totalReturned Total value refunded
     */
    public function __construct(
        public array $returnedCoins,
        public float $totalReturned
    ) {
    }
}