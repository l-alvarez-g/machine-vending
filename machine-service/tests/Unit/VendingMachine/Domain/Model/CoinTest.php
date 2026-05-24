<?php
declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InvalidCoinException;
use App\VendingMachine\Domain\Model\Coin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CoinTest extends TestCase
{
    #[DataProvider('validCoinsProvider')]
    public function testItCanBeCreatedWithValidValues(float $inputValue, int $expectedCents): void
    {
        $coin = new Coin($inputValue);

        $this->assertSame($expectedCents, $coin->amountInCents);
        $this->assertSame($inputValue, $coin->amount());
    }

    /**
     * @return array<string, array{0: float|int, 1: int}>
     */
    public static function validCoinsProvider(): array
    {
        return [
            'Nickel'      => [0.05, 5],
            'Dime'        => [0.10, 10],
            'Quarter'     => [0.25, 25],
            'Half Dollar' => [0.50, 50], // Proving the new domain flexibility
            'Dollar'      => [1.0, 100],
            'Dollar as Integer (1.0 casted)' => [1, 100],
        ];
    }

    #[DataProvider('invalidCoinsProvider')]
    public function testItThrowsExceptionForInvalidCoin(float $invalidValue): void
    {
        $this->expectException(InvalidCoinException::class);
        $this->expectExceptionMessage(sprintf('Invalid coin value: %s', $invalidValue));

        new Coin($invalidValue);
    }

    /**
     * @return array<string, array{0: float|int}>
     */
    public static function invalidCoinsProvider(): array
    {
        return [
            'Zero value'     => [0.0],
            'Negative value' => [-0.25],
            'Deep negative'  => [-5.0],
        ];
    }
}