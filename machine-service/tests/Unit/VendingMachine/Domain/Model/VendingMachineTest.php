<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InsufficientFundsException;
use App\VendingMachine\Domain\Exception\OutOfStockException;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use App\VendingMachine\Domain\Model\MoneyCollection;
use App\VendingMachine\Domain\Model\Product;
use App\VendingMachine\Domain\Model\VendingMachine;
use PHPUnit\Framework\TestCase;

final class VendingMachineTest extends TestCase
{
    private VendingMachine $machine;
    private Product $soda;

    protected function setUp(): void
    {
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);

        // Fixed: Aggregate Root now requires an explicit Identity (ID)
        $this->machine = new VendingMachine('vm-uuid-001', $policy);

        // Fixed: Value Objects must be instantiated with integers (cents)
        $this->soda = new Product('Soda', 150);

        // Service the machine with 1 Soda and some initial change (two 25 cent coins)
        $this->machine->serviceMachine(
            new MoneyCollection(new Coin(25), new Coin(25)),
            ['Soda' => ['product' => $this->soda, 'quantity' => 1]]
        );
    }

    public function testItCanInsertAndReturnCoins(): void
    {
        $this->machine->insertCoin(new Coin(100));
        $this->machine->insertCoin(new Coin(25));

        $returned = $this->machine->returnCoins();

        $this->assertSame(125, $returned->totalInCents());
        $this->assertSame(0, $this->machine->returnCoins()->totalInCents(), 'Machine should be empty after return.');
    }

    public function testItVendsProductWithExactChange(): void
    {
        $this->machine->insertCoin(new Coin(100));
        $this->machine->insertCoin(new Coin(25));
        $this->machine->insertCoin(new Coin(25));

        $result = $this->machine->vendProduct('Soda');

        // Fixed: Use encapsulated accessors
        $this->assertSame('Soda', $result->product()->name());
        $this->assertSame(0, $result->change()->totalInCents());
    }

    public function testItVendsProductAndReturnsChange(): void
    {
        // Insert 200 cents (two 100 coins) for a 150 cents Soda
        $this->machine->insertCoin(new Coin(100));
        $this->machine->insertCoin(new Coin(100));

        $result = $this->machine->vendProduct('Soda');

        $this->assertSame('Soda', $result->product()->name());
        $this->assertSame(50, $result->change()->totalInCents());
        $this->assertCount(2, $result->change()->coins()); 
    }

    public function testItThrowsExceptionWhenInsufficientFunds(): void
    {
        $this->machine->insertCoin(new Coin(100));

        $this->expectException(InsufficientFundsException::class);
        // Fixed: Asserting the rich exception message provided by our named constructors
        $this->expectExceptionMessage('Not enough money inserted. Inserted: 100 cents, Required: 150 cents.');

        $this->machine->vendProduct('Soda');
    }

    public function testItThrowsExceptionWhenOutOfStock(): void
    {
        $this->machine->insertCoin(new Coin(100));
        $this->machine->insertCoin(new Coin(100));

        $this->machine->vendProduct('Soda'); // First vend works, stock becomes 0

        $this->machine->insertCoin(new Coin(100));
        $this->machine->insertCoin(new Coin(100));

        $this->expectException(OutOfStockException::class);
        $this->expectExceptionMessage('The requested product "Soda" is currently out of stock.');

        $this->machine->vendProduct('Soda'); // Second vend fails
    }
}