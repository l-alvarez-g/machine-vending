<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class VendProductCommandHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $repository
    ) {
    }

    public function __invoke(VendProductCommand $command): VendProductResponse
    {
        // 1. Hydrate the specific aggregate
        $machine = $this->repository->get($command->machineId);

        // 2. Delegate the business action to the Domain
        $domainResult = $machine->vendProduct($command->productName);

        // 3. Persist state changes
        $this->repository->save($machine);

        // 4. Translate Domain Model to Application DTO protecting architectural boundaries
        return new VendProductResponse(
            $domainResult->product()->name(),
            $domainResult->product()->price(),
            $domainResult->change()->totalInCents() / 100
        );
    }
}