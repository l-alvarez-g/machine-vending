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
        $machine = $this->repository->get();

        $coins = array_map(
            static fn (float $amount): Coin => new Coin($amount),
            $command->initialChangeCoins
        );
        $initialChange = new MoneyCollection(...$coins);

        $domainInventory = [];
        foreach ($command->inventory as $productName => $productData) {
            $domainInventory[(string) $productName] = [
                'product' => new Product((string) $productName, (float) $productData['price']),
                'quantity' => (int) $productData['quantity'],
            ];
        }

        $machine->serviceMachine($initialChange, $domainInventory);

        $this->repository->save($machine);
    }
}