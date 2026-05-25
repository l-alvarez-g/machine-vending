<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\InsertCoinCommandHandler;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use App\VendingMachine\Infrastructure\Controller\Console\Action\InsertCoinAction;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

final class InsertCoinActionTest extends TestCase
{
    private VendingMachineRepositoryInterface&MockObject $repositoryMock;
    private OutputInterface&MockObject $outputMock;
    private InsertCoinAction $action;

    protected function setUp(): void
    {
        // 1. Mock the Infrastructure Port (Interface), which is completely valid in PHPUnit
        $this->repositoryMock = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        // 2. Instantiate the REAL Application layer handler with the mocked repository
        $handler = new InsertCoinCommandHandler($this->repositoryMock);

        $validCoins = ['0.05', '0.10', '0.25', '1'];

        // 3. Instantiate the Console Action
        $this->action = new InsertCoinAction($handler, $validCoins);
    }

    public function testItSupportsNumericTokens(): void
    {
        self::assertTrue($this->action->supports('0.25'));
        self::assertTrue($this->action->supports('1'));
        self::assertFalse($this->action->supports('GET-SODA'));
    }

    public function testItExecutesValidCoinInsertion(): void
    {
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $dummyMachine = new VendingMachine($policy);

        // El resto del test se mantiene idéntico
        $this->repositoryMock->expects(self::once())
            ->method('get')
            ->willReturn($dummyMachine);

        $this->repositoryMock->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(VendingMachine::class));

        $this->outputMock->expects(self::never())->method('writeln');

        $result = $this->action->execute('0.25', $this->outputMock);

        self::assertNull($result);
    }

    public function testItRejectsInvalidCoinValues(): void
    {
        // If the coin is invalid, the handler should NEVER be called (no DB interactions)
        $this->repositoryMock->expects(self::never())->method('get');
        $this->repositoryMock->expects(self::never())->method('save');

        // The console must print the specific error message
        $this->outputMock->expects(self::once())
            ->method('writeln')
            ->with('<error>Invalid coin value: 0.99</error>');

        $result = $this->action->execute('0.99', $this->outputMock);

        self::assertNull($result);
    }
}