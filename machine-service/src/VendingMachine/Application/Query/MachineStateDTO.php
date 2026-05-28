<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Query;

final readonly class MachineStateDTO
{
    /**
     * @param array<string, int> $vaultCoins Array of formatted coin amounts (e.g., "1.00") mapped to their count.
     * @param array<string, array{price: float, quantity: int}> $inventory
     */
    public function __construct(
        public array $vaultCoins,
        public array $inventory
    ) {
    }
}