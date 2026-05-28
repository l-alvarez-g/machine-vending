<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Query\GetMachineStateQueryHandler;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use App\VendingMachine\Infrastructure\Controller\Console\Action\StatusAction;
use App\VendingMachine\Infrastructure\Controller\Console\Presenter\VendingMachinePresenter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

final class StatusActionTest extends TestCase
{
    private VendingMachineRepositoryInterface&MockObject $repositoryMock;
    private OutputInterface&MockObject $outputMock;
    private StatusAction $action;
    private const MACHINE_ID = 'cli-machine-001';

    protected function setUp(): void
    {
        // 1. Mock the infrastructure boundary
        $this->repositoryMock = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        // 2. Instantiate the real application layer query handler
        $queryHandler = new GetMachineStateQueryHandler($this->repositoryMock);

        // 3. Instantiate the real presenter
        $presenter = new VendingMachinePresenter();

        // 4. Inject the machine ID into the action
        $this->action = new StatusAction($queryHandler, $presenter, self::MACHINE_ID);
    }

    public function testItSupportsStatusToken(): void
    {
        self::assertTrue($this->action->supports('STATUS'));
        self::assertFalse($this->action->supports('STATUS-NOW'));
    }

    public function testItQueriesStateAndDelegatesToPresenter(): void
    {
        // 1. Instantiate the real vending machine with its ID
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $realMachine = new VendingMachine(self::MACHINE_ID, $policy);

        // 2. Verify the query handler requests the machine with the correct ID
        $this->repositoryMock->expects(self::once())
            ->method('get')
            ->with(self::MACHINE_ID)
            ->willReturn($realMachine);

        // 3. Verify the output formatting
        $this->outputMock->expects(self::exactly(6))
            ->method('writeln')
            ->with(self::logicalOr(
                self::stringContains('=== CURRENT MACHINE STATE ==='),
                self::stringContains('Vault (Available Change):'),
                self::stringContains('[Empty]'),
                self::stringContains('Inventory:'),
                self::stringContains('=========================')
            ));

        $result = $this->action->execute('STATUS', $this->outputMock);

        // 4. Assert that the action returns null (as it delegates directly to the output)
        self::assertNull($result);
    }
}