<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class ReturnCoinsCommandHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $repository
    ) {
    }

    public function __invoke(ReturnCoinsCommand $command): ReturnCoinsResponse
    {
        // 1. Hydrate the correct aggregate
        $machine = $this->repository->get($command->machineId);

        // 2. Execute business logic
        $returnedCoins = $machine->returnCoins();

        // 3. Persist state changes
        $this->repository->save($machine);

        // 4. Translate Domain Model to Application DTO to protect architectural boundaries
        $totalReturnedAsFloat = $returnedCoins->totalInCents() / 100;

        return new ReturnCoinsResponse($totalReturnedAsFloat);
    }
}