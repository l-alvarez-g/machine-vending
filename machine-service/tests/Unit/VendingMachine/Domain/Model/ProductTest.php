<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InvalidProductException;
use App\VendingMachine\Domain\Model\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testItCanBeCreatedWithValidCents(): void
    {
        $product = new Product('Soda', 150);

        $this->assertSame('Soda', $product->name());
        $this->assertSame(150, $product->priceInCents());
        $this->assertSame(1.50, $product->price());
    }

    public function testItCanBeCreatedFromFloatSafely(): void
    {
        $product = Product::fromFloatPrice('Soda', 1.50);

        $this->assertSame('Soda', $product->name());
        $this->assertSame(150, $product->priceInCents());
    }

    public function testItSanitizesTheNameDuringConstruction(): void
    {
        $product = new Product('  Mineral Water   ', 200);

        $this->assertSame('Mineral Water', $product->name());
    }

    public function testItThrowsExceptionForEmptyName(): void
    {
        $this->expectException(InvalidProductException::class);
        $this->expectExceptionMessage('Product name cannot be empty.');

        new Product('   ', 100);
    }

    #[DataProvider('invalidPricesProvider')]
    public function testItThrowsExceptionForNegativeOrZeroPrice(int $invalidPriceInCents): void
    {
        $this->expectException(InvalidProductException::class);
        $this->expectExceptionMessage(
            sprintf('Product price must be greater than zero, got: %d cents.', $invalidPriceInCents)
        );

        new Product('Water', $invalidPriceInCents);
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function invalidPricesProvider(): array
    {
        return [
            'Zero price'     => [0],
            'Negative price' => [-50],
        ];
    }

    public function testValueObjectEquality(): void
    {
        $sodaA = new Product('Soda', 150);
        $sodaB = new Product('Soda', 150);
        $water = new Product('Water', 150);
        $expensiveSoda = new Product('Soda', 200);

        $this->assertTrue($sodaA->equals($sodaB), 'Products with the same name and price should be equal.');
        $this->assertFalse($sodaA->equals($water), 'Products with different names should not be equal.');
        $this->assertFalse($sodaA->equals($expensiveSoda), 'Products with different prices should not be equal.');
    }
}