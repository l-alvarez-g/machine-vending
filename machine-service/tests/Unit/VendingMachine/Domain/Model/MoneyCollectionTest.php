<?php
declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\MoneyCollection;
use PHPUnit\Framework\TestCase;

final class MoneyCollectionTest extends TestCase
{
    public function testItCalculatesTotalCorrectly(): void
    {
        $collection = new MoneyCollection(
            new Coin(1.00),
            new Coin(0.25)
        );

        $this->assertSame(125, $collection->totalInCents());
        $this->assertCount(2, $collection->coins());
    }

    public function testItIsImmutableWhenAddingCoins(): void
    {
        $initialCollection = new MoneyCollection(new Coin(0.10));
        $newCollection = $initialCollection->add(new Coin(0.25));

        $this->assertNotSame($initialCollection, $newCollection);
        $this->assertSame(10, $initialCollection->totalInCents());
        $this->assertSame(35, $newCollection->totalInCents());
    }

    public function testItCanBeEmpty(): void
    {
        $collection = MoneyCollection::empty();

        $this->assertSame(0, $collection->totalInCents());
        $this->assertCount(0, $collection->coins());
    }
}