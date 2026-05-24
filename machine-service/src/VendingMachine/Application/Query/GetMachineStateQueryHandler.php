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

    /**
     * @param GetMachineStateQuery $query
     * * Note: Although $query is unused in the method body (since there is only one machine),
     * it MUST be kept in the signature. In CQRS and Message Bus architectures (like
     * Symfony Messenger or Tactician), the type-hint of the argument is used as the
     * routing mechanism to map the dispatched message to this specific handler.
     */
    public function __invoke(GetMachineStateQuery $query): MachineStateDTO
    {
        $machine = $this->repository->get();

        // 1. Map the vault (Available Change)
        /** @var array<string, int> $coinCounts */
        $coinCounts = [];

        foreach ($machine->vault()->coins() as $coin) {
            // Formatting to 2 decimals prevents PHP from automatically coercing 
            // whole number strings (like "1" for a dollar) into integers.
            // This strictly satisfies the array<string, int> contract of the DTO.
            $valStr = number_format($coin->amount(), 2, '.', '');
            $coinCounts[$valStr] = ($coinCounts[$valStr] ?? 0) + 1;
        }

        // Sort keys descending (highest coin value first)
        krsort($coinCounts);

        // 2. Map the inventory (Products)
        $inventoryDTO = [];
        foreach ($machine->inventory() as $name => $data) {
            $inventoryDTO[$name] = [
                'price' => $data['product']->priceInCents / 100,
                'quantity' => $data['quantity']
            ];
        }

        return new MachineStateDTO($coinCounts, $inventoryDTO);
    }
}