<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

final readonly class VendProductCommand
{
    public function __construct(
        public string $machineId,
        public string $productName
    ) {
    }
}