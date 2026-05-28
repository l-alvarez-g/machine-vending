<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Application\Command;

use App\VendingMachine\Application\Command\VendProductCommand;
use App\VendingMachine\Application\Command\VendProductCommandHandler;
use App\VendingMachine\Application\Command\VendProductResponse;
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
        $machineId = 'vm-uuid-001';
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $machine = new VendingMachine($machineId, $policy);

        // Preparing the domain state strictly with integers (cents)
        $machine->serviceMachine(
            new MoneyCollection(new Coin(25), new Coin(25)),
            ['Soda' => ['product' => new Product('Soda', 150), 'quantity' => 1]]
        );
        $machine->insertCoin(new Coin(100));
        $machine->insertCoin(new Coin(100));

        // Expectation: Retrieve by specific ID
        $this->repository->expects($this->once())
            ->method('get')
            ->with($machineId)
            ->willReturn($machine);

        // Expectation: Persist the identical instance
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($machine));

        // Execution: Command receives the ID
        $command = new VendProductCommand($machineId, 'Soda');

        /** @var VendProductResponse $response */
        $response = $this->handler->__invoke($command);

        // Asserting against the Application DTO primitives (floats)
        $this->assertInstanceOf(VendProductResponse::class, $response);
        $this->assertSame('Soda', $response->productName);
        $this->assertSame(1.50, $response->productPrice);
        $this->assertSame(0.50, $response->changeReturned); // 2.00 inserted - 1.50 price = 0.50 change
    }
}