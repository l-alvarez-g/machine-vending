<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Query;

final readonly class GetMachineStateQuery
{
    public function __construct(
        public string $machineId
    ) {
    }
}