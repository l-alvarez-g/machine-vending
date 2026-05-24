<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Application\Command;

use App\VendingMachine\Application\Command\ReturnCoinsCommand;
use App\VendingMachine\Application\Command\ReturnCoinsCommandHandler;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ReturnCoinsCommandHandlerTest extends TestCase
{
    private VendingMachineRepositoryInterface&MockObject $repository;
    private ReturnCoinsCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->handler = new ReturnCoinsCommandHandler($this->repository);
    }

    public function testItReturnsCoinsAndSavesState(): void
    {
        $machine = new VendingMachine();
        $machine->insertCoin(new Coin(0.25));
        $machine->insertCoin(new Coin(0.10));

        $this->repository->expects($this->once())
            ->method('get')
            ->willReturn($machine);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->equalTo($machine));

        $command = new ReturnCoinsCommand();
        $returnedCoins = $this->handler->__invoke($command);

        $this->assertSame(35, $returnedCoins->totalInCents());
        $this->assertSame(0, $machine->returnCoins()->totalInCents());
    }
}