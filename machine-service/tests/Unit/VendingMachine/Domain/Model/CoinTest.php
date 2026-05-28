<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InvalidCoinException;
use App\VendingMachine\Domain\Model\Coin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CoinTest extends TestCase
{
    #[DataProvider('validCentsProvider')]
    public function testItCanBeCreatedWithValidCents(int $cents): void
    {
        $coin = new Coin($cents);

        $this->assertSame($cents, $coin->amountInCents());
        $this->assertSame((float) ($cents / 100), $coin->amount());
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function validCentsProvider(): array
    {
        return [
            'Nickel'      => [5],
            'Dime'        => [10],
            'Quarter'     => [25],
            'Half Dollar' => [50],
            'Dollar'      => [100],
        ];
    }

    #[DataProvider('validFloatsProvider')]
    public function testItCanBeCreatedFromFloatSafely(float $inputValue, int $expectedCents): void
    {
        $coin = Coin::fromFloat($inputValue);

        $this->assertSame($expectedCents, $coin->amountInCents());
    }

    /**
     * @return array<string, array{0: float, 1: int}>
     */
    public static function validFloatsProvider(): array
    {
        return [
            'Nickel'  => [0.05, 5],
            'Quarter' => [0.25, 25],
            'Dollar'  => [1.0, 100],
        ];
    }

    #[DataProvider('invalidCentsProvider')]
    public function testItThrowsExceptionForInvalidCents(int $invalidCents): void
    {
        $this->expectException(InvalidCoinException::class);
        $this->expectExceptionMessage(
            sprintf('Invalid coin denomination. Amount must be strictly positive, got: %d cents.', $invalidCents)
        );

        new Coin($invalidCents);
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function invalidCentsProvider(): array
    {
        return [
            'Zero value'     => [0],
            'Negative value' => [-25],
            'Deep negative'  => [-500],
        ];
    }

    public function testValueObjectEquality(): void
    {
        $coinA = new Coin(25);
        $coinB = new Coin(25);
        $coinC = new Coin(50);

        $this->assertTrue($coinA->equals($coinB), 'Coins with the same cents should be equal.');
        $this->assertFalse($coinA->equals($coinC), 'Coins with different cents should not be equal.');
    }
}