<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Application\Command;

use App\VendingMachine\Application\Command\InsertCoinCommand;
use App\VendingMachine\Application\Command\InsertCoinCommandHandler;
use App\VendingMachine\Domain\Model\VendingMachine;
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
        $machine = new VendingMachine();

        $this->repository->expects($this->once())
            ->method('get')
            ->willReturn($machine);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->equalTo($machine));

        $command = new InsertCoinCommand(1.0);
        $this->handler->__invoke($command);

        $this->assertSame(100, $machine->returnCoins()->totalInCents());
    }
}