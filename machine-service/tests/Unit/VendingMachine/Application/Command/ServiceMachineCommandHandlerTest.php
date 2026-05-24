<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Application\Command;

use App\VendingMachine\Application\Command\ServiceMachineCommand;
use App\VendingMachine\Application\Command\ServiceMachineCommandHandler;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ServiceMachineCommandHandlerTest extends TestCase
{
    private VendingMachineRepositoryInterface&MockObject $repository;
    private ServiceMachineCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->handler = new ServiceMachineCommandHandler($this->repository);
    }

    public function testItServicesMachineAndSavesState(): void
    {
        $machine = new VendingMachine();

        $this->repository->expects($this->once())
            ->method('get')
            ->willReturn($machine);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->equalTo($machine));

        $command = new ServiceMachineCommand(
            [0.25, 0.10],
            ['Water' => ['price' => 0.65, 'quantity' => 10]]
        );

        $this->handler->__invoke($command);

        // Asserting state change by trying to insert a coin and buying the newly added product
        $machine->insertCoin(new Coin(1.00));
        $result = $machine->vendProduct('Water');

        $this->assertSame('Water', $result->product->name);
        $this->assertSame(35, $result->change->totalInCents());
    }
}