<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Application\Command;

use App\VendingMachine\Application\Command\ReturnCoinsCommand;
use App\VendingMachine\Application\Command\ReturnCoinsCommandHandler;
use App\VendingMachine\Application\Command\ReturnCoinsResponse;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
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
        $machineId = 'vm-uuid-001';
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $machine = new VendingMachine($machineId, $policy);

        // Supplying internal state directly with pure integer constructors
        $machine->insertCoin(new Coin(25));
        $machine->insertCoin(new Coin(10));

        // Expectation: Retrieve by specific ID
        $this->repository->expects($this->once())
            ->method('get')
            ->with($machineId)
            ->willReturn($machine);

        // Expectation: Persist the identical instance
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($machine));

        $command = new ReturnCoinsCommand($machineId);

        /** @var ReturnCoinsResponse $response */
        $response = $this->handler->__invoke($command);

        // Asserting against the Application DTO (floats), not the Domain Model
        $this->assertInstanceOf(ReturnCoinsResponse::class, $response);
        $this->assertSame(0.35, $response->totalReturned);

        // Asserting internal state was cleared correctly
        $this->assertSame(0, $machine->returnCoins()->totalInCents());
    }
}