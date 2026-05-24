<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Persistence;

use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;

final class InMemoryVendingMachineRepository implements VendingMachineRepositoryInterface
{
    private ?VendingMachine $machine = null;

    public function get(): VendingMachine
    {
        if ($this->machine === null) {
            // We inject the default US coin policy for the in-memory persistence layer
            $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
            $this->machine = new VendingMachine($policy);
        }

        return $this->machine;
    }

    public function save(VendingMachine $vendingMachine): void
    {
        $this->machine = $vendingMachine;
    }
}