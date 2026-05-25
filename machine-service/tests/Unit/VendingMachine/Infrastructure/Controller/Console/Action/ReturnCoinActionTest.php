<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\ReturnCoinsCommandHandler;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
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

    protected function setUp(): void
    {
        // 1. Mock the Infrastructure Port (Interface)
        $this->repositoryMock = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        // 2. Instantiate the REAL Application layer handler
        $handler = new ReturnCoinsCommandHandler($this->repositoryMock);

        // 3. Instantiate the REAL Presenter
        $presenter = new VendingMachinePresenter();

        // 4. Instantiate the Console Action
        $this->action = new ReturnCoinAction($handler, $presenter);
    }

    public function testItSupportsReturnCoinToken(): void
    {
        self::assertTrue($this->action->supports('RETURN-COIN'));
        self::assertFalse($this->action->supports('GET-SODA'));
    }

    public function testItExecutesReturnCoinsAndFormatsOutput(): void
    {
        // 1. Instantiate the REAL VendingMachine using the exact policy you discovered earlier
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $realMachine = new VendingMachine($policy);

        // Optional: If you want to test returning specific coins, insert them into $realMachine right here
        // using your domain methods (e.g., $realMachine->insertCoin(...)). 

        // 2. The repository will return our real machine
        $this->repositoryMock->expects(self::once())
            ->method('get')
            ->willReturn($realMachine);

        // 3. The repository should save the machine after the operation
        $this->repositoryMock->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(VendingMachine::class));

        // 4. Execute the action
        $result = $this->action->execute('RETURN-COIN', $this->outputMock);

        // 5. Assert the expected output. A fresh machine returns an empty collection.
        self::assertSame('', $result);
    }
}