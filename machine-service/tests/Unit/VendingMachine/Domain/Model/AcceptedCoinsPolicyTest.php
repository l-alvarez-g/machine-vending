<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InvalidCoinException;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use App\VendingMachine\Domain\Model\Coin;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AcceptedCoinsPolicyTest extends TestCase
{
    public function testItIsSatisfiedByValidCoins(): void
    {
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $validCoin = new Coin(25);

        $this->assertTrue($policy->isSatisfiedBy($validCoin));
    }

    public function testItIsNotSatisfiedByInvalidCoins(): void
    {
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $invalidCoin = new Coin(50); // 50 cents is not in the policy

        $this->assertFalse($policy->isSatisfiedBy($invalidCoin));
    }

    public function testItThrowsDomainExceptionWhenAssertingInvalidCoin(): void
    {
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $invalidCoin = new Coin(50);

        $this->expectException(InvalidCoinException::class);
        $this->expectExceptionMessage('The machine does not accept the coin denomination of 50 cents.');

        $policy->assertIsSatisfiedBy($invalidCoin);
    }

    public function testItThrowsExceptionIfConfigurationIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The accepted coins policy must contain at least one valid coin denomination.');

        new AcceptedCoinsPolicy([]);
    }

    #[DataProvider('invalidConfigurationProvider')]
    public function testItThrowsExceptionIfConfigurationContainsInvalidValues(int $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('Invalid coin denomination: %d. Must be strictly positive.', $invalidValue)
        );

        new AcceptedCoinsPolicy([25, 100, $invalidValue]);
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function invalidConfigurationProvider(): array
    {
        return [
            'Zero value in config'     => [0],
            'Negative value in config' => [-10],
        ];
    }
}