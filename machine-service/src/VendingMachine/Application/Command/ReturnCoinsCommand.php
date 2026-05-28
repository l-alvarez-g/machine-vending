<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

final readonly class ReturnCoinsCommand
{
    public function __construct(
        public string $machineId
    ) {
    }
}