<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InsufficientFundsException;
use App\VendingMachine\Domain\Exception\OutOfStockException;
use App\VendingMachine\Domain\Model\Coin;
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
        $this->machine = new VendingMachine();
        $this->soda = new Product('Soda', 1.50);

        // Service the machine with 1 Soda and some initial change (two 0.25 coins)
        $this->machine->serviceMachine(
            new MoneyCollection(new Coin(0.25), new Coin(0.25)),
            ['Soda' => ['product' => $this->soda, 'quantity' => 1]]
        );
    }

    public function testItCanInsertAndReturnCoins(): void
    {
        $this->machine->insertCoin(new Coin(1.00));
        $this->machine->insertCoin(new Coin(0.25));

        $returned = $this->machine->returnCoins();

        $this->assertSame(125, $returned->totalInCents());
        $this->assertSame(0, $this->machine->returnCoins()->totalInCents(), 'Machine should be empty after return');
    }

    public function testItVendsProductWithExactChange(): void
    {
        $this->machine->insertCoin(new Coin(1.00));
        $this->machine->insertCoin(new Coin(0.25));
        $this->machine->insertCoin(new Coin(0.25));

        $result = $this->machine->vendProduct('Soda');

        $this->assertSame('Soda', $result->product->name);
        $this->assertSame(0, $result->change->totalInCents());
    }

    public function testItVendsProductAndReturnsChange(): void
    {
        // Insert 2.00 (two 1.00 coins) for a 1.50 Soda
        $this->machine->insertCoin(new Coin(1.00));
        $this->machine->insertCoin(new Coin(1.00));

        $result = $this->machine->vendProduct('Soda');

        $this->assertSame('Soda', $result->product->name);
        $this->assertSame(50, $result->change->totalInCents());
        // Since the machine only had two 0.25 coins in the vault from setUp, it should return them
        $this->assertCount(2, $result->change->coins()); 
    }

    public function testItThrowsExceptionWhenInsufficientFunds(): void
    {
        $this->machine->insertCoin(new Coin(1.00));

        $this->expectException(InsufficientFundsException::class);
        $this->machine->vendProduct('Soda');
    }

    public function testItThrowsExceptionWhenOutOfStock(): void
    {
        $this->machine->insertCoin(new Coin(1.00));
        $this->machine->insertCoin(new Coin(1.00));

        $this->machine->vendProduct('Soda'); // First vend works, stock becomes 0

        $this->machine->insertCoin(new Coin(1.00));
        $this->machine->insertCoin(new Coin(1.00));

        $this->expectException(OutOfStockException::class);
        $this->machine->vendProduct('Soda'); // Second vend fails
    }
}