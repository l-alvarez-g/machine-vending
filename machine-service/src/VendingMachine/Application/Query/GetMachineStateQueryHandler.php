<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Query;

use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class GetMachineStateQueryHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $repository
    ) {
    }

    public function __invoke(GetMachineStateQuery $query): MachineStateDTO
    {
        // Pragmatic CQRS: Hydrating the aggregate for reading.
        // In a highly scaled system, this would query a Read Model (e.g., DBAL projection) directly.
        $machine = $this->repository->get($query->machineId);

        /** @var array<string, int> $coinCounts */
        $coinCounts = [];

        foreach ($machine->vault()->coins() as $coin) {
            // Formatting ensures strict string keys to satisfy array<string, int> contract
            $valStr = number_format($coin->amount(), 2, '.', '');
            $coinCounts[$valStr] = ($coinCounts[$valStr] ?? 0) + 1;
        }

        krsort($coinCounts);

        /** @var array<string, array{price: float, quantity: int}> $inventoryDTO */
        $inventoryDTO = [];

        foreach ($machine->inventory() as $name => $data) {
            $inventoryDTO[(string) $name] = [
                'price'    => $data['product']->priceInCents() / 100, // Fixed: using encapsulated accessor
                'quantity' => $data['quantity']
            ];
        }

        return new MachineStateDTO($coinCounts, $inventoryDTO);
    }
}