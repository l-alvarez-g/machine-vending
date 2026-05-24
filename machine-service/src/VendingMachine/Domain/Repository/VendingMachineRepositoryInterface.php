<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Repository;

use App\VendingMachine\Domain\Model\VendingMachine;

interface VendingMachineRepositoryInterface
{
    public function get(): VendingMachine;

    public function save(VendingMachine $vendingMachine): void;
}