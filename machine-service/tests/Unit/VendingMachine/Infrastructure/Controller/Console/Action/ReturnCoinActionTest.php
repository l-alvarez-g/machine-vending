<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\ReturnCoinsCommandHandler;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use App\VendingMachine\Infrastructure\Controller\Console\Action\ReturnCoinAction;
use App\VendingMachine\Infrastructure\Controller\Console\Presenter\VendingMachinePresenter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

final class ReturnCoinActionTest extends TestCase
{
    private VendingMachineRepositoryInterface&MockObject $repositoryMock;
    private OutputInterface&MockObject $outputMock;
    private ReturnCoinAction $action;
    private const MACHINE_ID = 'cli-machine-001';

    protected function setUp(): void
    {
        // 1. Mock the Infrastructure Port
        $this->repositoryMock = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        // 2. Instantiate the real Application layer handler
        $handler = new ReturnCoinsCommandHandler($this->repositoryMock);

        // 3. Instantiate the real Presenter
        $presenter = new VendingMachinePresenter();

        // 4. Instantiate the Console Action, injecting the machine ID
        $this->action = new ReturnCoinAction($handler, $presenter, self::MACHINE_ID);
    }

    public function testItSupportsReturnCoinToken(): void
    {
        self::assertTrue($this->action->supports('RETURN-COIN'));
        self::assertFalse($this->action->supports('GET-SODA'));
    }

    public function testItExecutesReturnCoinsAndFormatsOutput(): void
    {
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $realMachine = new VendingMachine(self::MACHINE_ID, $policy);

        // Simulate coin insertion to test the return functionality
        $realMachine->insertCoin(new Coin(25));
        $realMachine->insertCoin(new Coin(10));

        // The repository should return our real machine instance
        $this->repositoryMock->expects(self::once())
            ->method('get')
            ->with(self::MACHINE_ID)
            ->willReturn($realMachine);

        // The repository should save the machine state after the return operation
        $this->repositoryMock->expects(self::once())
            ->method('save')
            ->with(self::identicalTo($realMachine));

        // The Action executes the handler and uses the Presenter
        $result = $this->action->execute('RETURN-COIN', $this->outputMock);

        // Validate the exact formatted output: "0.25, 0.10"
        self::assertSame('0.25, 0.10', $result);
    }
}