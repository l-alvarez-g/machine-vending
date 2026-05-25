<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Infrastructure\Controller\Console\Parser;

use App\VendingMachine\Application\Query\MachineStateDTO;
use App\VendingMachine\Infrastructure\Controller\Console\Parser\ServicePayloadParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ServicePayloadParserTest extends TestCase
{
    private ServicePayloadParser $parser;
    private MachineStateDTO $dummyState;

    protected function setUp(): void
    {
        $validCoins = ['0.05', '0.10', '0.25', '1'];
        $productPrices = ['WATER' => 0.65, 'SODA' => 1.50];

        $this->parser = new ServicePayloadParser($validCoins, $productPrices);

        $this->dummyState = new MachineStateDTO(
            ['0.10' => 5], // Current vault: 5 dimes
            ['WATER' => ['price' => 0.65, 'quantity' => 10]] // Current inventory
        );
    }

    public function testItParsesFullPayloadCorrectly(): void
    {
        $payload = '0.25:2;1:1|SODA:5';
        $result = $this->parser->parse($payload, $this->dummyState);

        $expectedCoins = [0.25, 0.25, 1.0];
        $expectedInventory = [
            'SODA' => ['price' => 1.50, 'quantity' => 5]
        ];

        self::assertSame($expectedCoins, $result['coins']);
        self::assertSame($expectedInventory, $result['inventory']);
    }

    public function testItPreservesStateWhenPayloadIsEmpty(): void
    {
        $payload = '|'; // Empty coins and empty inventory
        $result = $this->parser->parse($payload, $this->dummyState);

        $expectedCoins = [0.10, 0.10, 0.10, 0.10, 0.10]; // From dummy state

        self::assertSame($expectedCoins, $result['coins']);
        self::assertSame($this->dummyState->inventory, $result['inventory']);
    }

    public function testItThrowsExceptionOnInvalidCoin(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid coin detected: 3.50');

        $this->parser->parse('3.50:1|SODA:5', $this->dummyState);
    }

    public function testItThrowsExceptionOnUnknownProduct(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown product: CHORIZO');

        $this->parser->parse('1:1|CHORIZO:10', $this->dummyState);
    }
}