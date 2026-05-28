<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class InsertCoinCommandHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $repository
    ) {
    }

    public function __invoke(InsertCoinCommand $command): void
    {
        // 1. Retrieve the specific Aggregate Root
        $machine = $this->repository->get($command->machineId);

        // 2. Translate application primitive to Domain Value Object
        $coin = Coin::fromFloat($command->amount);

        // 3. Delegate the business action to the Domain
        $machine->insertCoin($coin);

        // 4. Persist the updated state (and handle Outbox events implicitly via infrastructure)
        $this->repository->save($machine);
    }
}