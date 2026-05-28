<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Query;

final readonly class MachineStateDTO
{
    /**
     * @param array<array-key, int> $vaultCoins
     * @param array<string, array{price: float, quantity: int}> $inventory
     */
    public function __construct(
        public array $vaultCoins,
        public array $inventory
    ) {
    }
}