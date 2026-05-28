<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\MoneyCollection;
use App\VendingMachine\Domain\Model\Product;
use App\VendingMachine\Domain\Model\VendResult;
use PHPUnit\Framework\TestCase;

final class VendResultTest extends TestCase
{
    public function testItEncapsulatesResultCorrectly(): void
    {
        $product = new Product('Water', 100);
        $change = new MoneyCollection(new Coin(25), new Coin(25));

        $result = new VendResult($product, $change);

        $this->assertSame($product, $result->product());
        $this->assertSame($change, $result->change());

        $this->assertSame('Water', $result->product()->name());
        $this->assertSame(50, $result->change()->totalInCents());
    }
}