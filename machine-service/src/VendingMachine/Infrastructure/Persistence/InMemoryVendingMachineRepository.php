<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Persistence;

use App\VendingMachine\Domain\Exception\VendingMachineNotFoundException;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;

final class InMemoryVendingMachineRepository implements VendingMachineRepositoryInterface
{
    /**
     * @var array<string, VendingMachine>
     */
    private array $machines = [];

    /**
     * Retrieves a VendingMachine by its unique identifier.
     *
     * @throws VendingMachineNotFoundException
     */
    public function get(string $id): VendingMachine
    {
        if (!array_key_exists($id, $this->machines)) {
            throw VendingMachineNotFoundException::withId($id);
        }

        return $this->machines[$id];
    }

    /**
     * Persists the VendingMachine aggregate state.
     */
    public function save(VendingMachine $vendingMachine): void
    {
        // We use the Aggregate Root's identity as the storage key
        $this->machines[$vendingMachine->id()] = $vendingMachine;
    }
}