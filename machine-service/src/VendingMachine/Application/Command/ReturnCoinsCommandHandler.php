<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

use App\VendingMachine\Domain\Model\MoneyCollection;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class ReturnCoinsCommandHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $repository
    ) {
    }

    public function __invoke(ReturnCoinsCommand $command): MoneyCollection
    {
        $machine = $this->repository->get();

        $returnedCoins = $machine->returnCoins();

        $this->repository->save($machine);

        return $returnedCoins;
    }
}