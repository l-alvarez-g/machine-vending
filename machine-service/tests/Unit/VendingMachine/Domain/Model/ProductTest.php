<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InvalidProductException;
use App\VendingMachine\Domain\Model\Product;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testItCanBeCreatedWithValidValues(): void
    {
        $product = new Product('Soda', 1.50);

        $this->assertSame('Soda', $product->name);
        $this->assertSame(150, $product->priceInCents);
        $this->assertSame(1.50, $product->price());
    }

    public function testItThrowsExceptionForEmptyName(): void
    {
        $this->expectException(InvalidProductException::class);
        $this->expectExceptionMessage('Product name cannot be empty');

        new Product('   ', 1.00);
    }

    public function testItThrowsExceptionForNegativeOrZeroPrice(): void
    {
        $this->expectException(InvalidProductException::class);
        $this->expectExceptionMessage('Product price must be greater than zero');

        new Product('Water', 0.0);
    }
}