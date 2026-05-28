<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Repository;

use App\VendingMachine\Domain\Exception\VendingMachineNotFoundException;
use App\VendingMachine\Domain\Model\VendingMachine;

interface VendingMachineRepositoryInterface
{
    /**
     * Retrieves a VendingMachine by its unique identifier.
     *
     * @throws VendingMachineNotFoundException
     */
    public function get(string $id): VendingMachine;

    /**
     * Persists the VendingMachine aggregate state.
     */
    public function save(VendingMachine $vendingMachine): void;
}