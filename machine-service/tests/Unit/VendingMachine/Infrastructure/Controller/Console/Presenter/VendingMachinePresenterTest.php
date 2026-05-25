<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Infrastructure\Controller\Console\Presenter;

use App\VendingMachine\Application\Query\MachineStateDTO;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\MoneyCollection;
use App\VendingMachine\Infrastructure\Controller\Console\Presenter\VendingMachinePresenter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

final class VendingMachinePresenterTest extends TestCase
{
    private VendingMachinePresenter $presenter;
    private OutputInterface&MockObject $outputMock;

    protected function setUp(): void
    {
        $this->presenter = new VendingMachinePresenter();
        $this->outputMock = $this->createMock(OutputInterface::class);
    }

    public function testItFormatsEmptyMoneyCollectionCorrectly(): void
    {
        // Use a REAL instance. Pure Value Objects are safe to instantiate directly.
        // Since it uses a variadic constructor, we pass no arguments for an empty collection.
        $collection = new MoneyCollection();

        $result = $this->presenter->formatCoins($collection);

        self::assertSame('', $result);
    }

    public function testItFormatsMultipleCoinsCorrectly(): void
    {
        // Instantiate real coins passing them as individual arguments (variadic), without the array brackets
        $collection = new MoneyCollection(
            new Coin(0.25), 
            new Coin(1.0)
        );

        $result = $this->presenter->formatCoins($collection);

        self::assertSame('0.25, 1', $result);
    }

    public function testItDisplaysStatusCorrectlyForEmptyMachine(): void
    {
        $emptyState = new MachineStateDTO([], []);

        // Ensure the presenter outputs exactly the expected 6 lines of formatting
        $this->outputMock->expects(self::exactly(6))
            ->method('writeln')
            ->with(self::logicalOr(
                self::stringContains('=== TEST STATUS ==='),
                self::stringContains('Vault (Available Change):'),
                self::stringContains('[Empty]'),
                self::stringContains('Inventory:'),
                self::stringContains('=========================')
            ));

        $this->presenter->displayStatus($this->outputMock, 'TEST STATUS', $emptyState);
    }
}