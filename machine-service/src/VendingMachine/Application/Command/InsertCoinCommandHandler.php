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
        $machine = $this->repository->get();
        $machine->insertCoin(new Coin($command->amount));
        $this->repository->save($machine);
    }
}