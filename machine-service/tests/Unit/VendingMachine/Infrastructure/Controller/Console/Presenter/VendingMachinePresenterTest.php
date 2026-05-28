<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Infrastructure\Controller\Console\Presenter;

use App\VendingMachine\Application\Command\ReturnCoinsResponse;
use App\VendingMachine\Application\Query\MachineStateDTO;
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

    public function testItFormatsEmptyResponseCorrectly(): void
    {
        $response = new ReturnCoinsResponse([], 0.0);
        $result = $this->presenter->formatReturnedCoins($response);

        self::assertSame('', $result);
    }

    public function testItFormatsMultipleCoinsCorrectly(): void
    {
        // Setup: Response containing 0.25 and 1.0
        $response = new ReturnCoinsResponse([0.25, 1.0], 1.25);
        $result = $this->presenter->formatReturnedCoins($response);

        // Actual output is '0.25, 1' based on your Presenter logic
        self::assertSame('0.25, 1', $result);
    }

    public function testItDisplaysStatusCorrectlyForEmptyMachine(): void
    {
        $emptyState = new MachineStateDTO([], []);

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