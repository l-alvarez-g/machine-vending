<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\MoneyCollection;
use App\VendingMachine\Domain\Model\Product;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class ServiceMachineCommandHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $repository
    ) {
    }

    public function __invoke(ServiceMachineCommand $command): void
    {
        // 1. Retrieve the specific Aggregate Root
        $machine = $this->repository->get($command->machineId);

        // 2. Translate application primitives to Domain Value Objects using safe factories
        $coins = array_map(
            static fn (float $amount): Coin => Coin::fromFloat($amount),
            $command->initialChangeCoins
        );

        $initialChange = new MoneyCollection(...$coins);

        $domainInventory = [];
        foreach ($command->inventory as $productName => $productData) {
            $domainInventory[(string) $productName] = [
                'product'  => Product::fromFloatPrice((string) $productName, (float) $productData['price']),
                'quantity' => (int) $productData['quantity'],
            ];
        }

        // 3. Delegate the business action to the Domain
        $machine->serviceMachine($initialChange, $domainInventory);

        // 4. Persist the updated state
        $this->repository->save($machine);
    }
}