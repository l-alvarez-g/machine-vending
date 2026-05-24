<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Application\Command;

use App\VendingMachine\Application\Command\VendProductCommand;
use App\VendingMachine\Application\Command\VendProductCommandHandler;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use App\VendingMachine\Domain\Model\MoneyCollection;
use App\VendingMachine\Domain\Model\Product;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class VendProductCommandHandlerTest extends TestCase
{
    private VendingMachineRepositoryInterface&MockObject $repository;
    private VendProductCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->handler = new VendProductCommandHandler($this->repository);
    }

    public function testItVendsProductAndSavesState(): void
    {
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $machine = new VendingMachine($policy);

        $machine->serviceMachine(
            new MoneyCollection(new Coin(0.25), new Coin(0.25)),
            ['Soda' => ['product' => new Product('Soda', 1.50), 'quantity' => 1]]
        );
        $machine->insertCoin(new Coin(1.0));
        $machine->insertCoin(new Coin(1.0));

        $this->repository->expects($this->once())
            ->method('get')
            ->willReturn($machine);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->equalTo($machine));

        $command = new VendProductCommand('Soda');
        $result = $this->handler->__invoke($command);

        $this->assertSame('Soda', $result->product->name);
        $this->assertSame(50, $result->change->totalInCents());
    }
}