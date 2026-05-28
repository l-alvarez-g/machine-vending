<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Application\Command;

use App\VendingMachine\Application\Command\InsertCoinCommand;
use App\VendingMachine\Application\Command\InsertCoinCommandHandler;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InsertCoinCommandHandlerTest extends TestCase
{
    private VendingMachineRepositoryInterface&MockObject $repository;
    private InsertCoinCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->handler = new InsertCoinCommandHandler($this->repository);
    }

    public function testItInsertsCoinAndSavesState(): void
    {
        $machineId = 'vm-uuid-001';
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $machine = new VendingMachine($machineId, $policy);

        // The handler must request the exact machine ID
        $this->repository->expects($this->once())
            ->method('get')
            ->with($machineId)
            ->willReturn($machine);

        // The handler must save the identical aggregate instance
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($machine));

        // The Application boundary receives floats (1.00 dollar), 
        // translating it to cents internally.
        $command = new InsertCoinCommand($machineId, 1.00);
        $this->handler->__invoke($command);

        $this->assertSame(100, $machine->returnCoins()->totalInCents());
    }
}