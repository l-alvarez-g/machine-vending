<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Application\Query;

use App\VendingMachine\Application\Query\GetMachineStateQuery;
use App\VendingMachine\Application\Query\GetMachineStateQueryHandler;
use App\VendingMachine\Application\Query\MachineStateDTO;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\MoneyCollection;
use App\VendingMachine\Domain\Model\Product;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class GetMachineStateQueryHandlerTest extends TestCase
{
    public function testItReturnsMachineStateDtoWithCorrectlyMappedData(): void
    {
        // 1. Arrange: Only mock the Infrastructure contract (Repository)
        $repositoryMock = $this->createMock(VendingMachineRepositoryInterface::class);

        // Create REAL domain objects
        $productWater = new Product('WATER', 0.65);
        $coins = [new Coin(0.25), new Coin(0.25), new Coin(0.10)];

        // Use Reflection to hydrate state directly without confusing PHPStan
        $vaultReflection = new ReflectionClass(MoneyCollection::class);
        $vault = $vaultReflection->newInstanceWithoutConstructor();

        // Hydrate vault property (Ensure 'coins' matches your internal property name in MoneyCollection)
        $vaultReflection->getProperty('coins')->setValue($vault, $coins);

        $machineReflection = new ReflectionClass(VendingMachine::class);
        $machine = $machineReflection->newInstanceWithoutConstructor();

        // Hydrate machine properties
        $machineReflection->getProperty('vault')->setValue($machine, $vault);
        $machineReflection->getProperty('inventory')->setValue($machine, [
            'WATER' => [
                'product' => $productWater,
                'quantity' => 10
            ]
        ]);

        // The mock repository returns our perfectly hydrated REAL Aggregate Root
        $repositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($machine);

        // 2. Act: Execute the Handler
        $handler = new GetMachineStateQueryHandler($repositoryMock);
        $dto = $handler->__invoke(new GetMachineStateQuery());

        // 3. Assert: Verify the DTO structure and values
        $this->assertInstanceOf(MachineStateDTO::class, $dto);

        // Assert Vault sorting and counting logic
        $expectedVault = [
            '0.25' => 2,
            '0.10' => 1
        ];
        $this->assertSame($expectedVault, $dto->vaultCoins);

        // Assert Inventory price mapping (converting cents to float)
        $expectedInventory = [
            'WATER' => [
                'price' => 0.65,
                'quantity' => 10
            ]
        ];
        $this->assertSame($expectedInventory, $dto->inventory);
    }
}