<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Persistence;

use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;

final class InMemoryVendingMachineRepository implements VendingMachineRepositoryInterface
{
    private ?VendingMachine $machine = null;

    public function get(): VendingMachine
    {
        if ($this->machine === null) {
            $this->machine = new VendingMachine();
        }

        return $this->machine;
    }

    public function save(VendingMachine $vendingMachine): void
    {
        $this->machine = $vendingMachine;
    }
}