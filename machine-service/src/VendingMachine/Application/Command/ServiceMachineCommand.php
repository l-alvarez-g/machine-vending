<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

final readonly class ServiceMachineCommand
{
    /**
     * @param float[] $initialChangeCoins
     * @param array<string, array{price: float, quantity: int}> $inventory
     */
    public function __construct(
        public array $initialChangeCoins,
        public array $inventory
    ) {
    }
}