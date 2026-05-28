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
        $machine = $this->repository->get($command->machineId);

        // Domain logic: Returns the exact physical coins inserted
        $returnedCollection = $machine->returnCoins();

        $this->repository->save($machine);

        // Translate Domain Objects (Coins) to Application Primitives (floats)
        $coinsFloats = array_map(
            static fn ($coin) => $coin->amountInCents() / 100,
            $returnedCollection->coins()
        );

        return new ReturnCoinsResponse(
            $coinsFloats,
            $returnedCollection->totalInCents() / 100
        );
    }
}