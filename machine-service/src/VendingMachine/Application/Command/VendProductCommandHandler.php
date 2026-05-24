<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

use App\VendingMachine\Domain\Model\VendResult;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class VendProductCommandHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $repository
    ) {
    }

    public function __invoke(VendProductCommand $command): VendResult
    {
        $machine = $this->repository->get();

        $result = $machine->vendProduct($command->productName);

        $this->repository->save($machine);

        return $result;
    }
}