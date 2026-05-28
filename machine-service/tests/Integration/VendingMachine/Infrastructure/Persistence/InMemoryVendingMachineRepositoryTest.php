<?php

declare(strict_types=1);

namespace App\Tests\Integration\VendingMachine\Infrastructure\Persistence;

use App\VendingMachine\Domain\Exception\VendingMachineNotFoundException;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use PHPUnit\Framework\TestCase;

final class InMemoryVendingMachineRepositoryTest extends TestCase
{
    public function testItSavesAndRetrievesTheSameInstanceById(): void
    {
        $repository = new InMemoryVendingMachineRepository();

        // 1. Arrange: Create a valid Aggregate Root explicitly
        $machineId = 'vm-test-001';
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $machine = new VendingMachine($machineId, $policy);

        // 2. Act: Mutate state and save
        $machine->insertCoin(new Coin(100)); // Using pure integer (cents)
        $repository->save($machine);

        // 3. Assert: Retrieve by ID and verify state retention
        $retrievedMachine = $repository->get($machineId);

        $this->assertSame(
            100,
            $retrievedMachine->returnCoins()->totalInCents(),
            'The repository must return the exact state that was saved.'
        );
        $this->assertSame($machine, $retrievedMachine, 'It should return the identical instance in memory.');
    }

    public function testItThrowsExceptionWhenMachineIsNotFound(): void
    {
        $repository = new InMemoryVendingMachineRepository();

        $this->expectException(VendingMachineNotFoundException::class);
        $this->expectExceptionMessage('Vending machine with ID "unknown-id" could not be found.');

        // Attempting to get a machine that was never saved
        $repository->get('unknown-id');
    }
}