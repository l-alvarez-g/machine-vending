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

    protected function setUp(): void
    {
        // 1. Mock the Infrastructure boundary
        $this->repositoryMock = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        // 2. Instantiate the REAL Application layer query handler
        $queryHandler = new GetMachineStateQueryHandler($this->repositoryMock);

        // 3. Instantiate the REAL Presenter
        $presenter = new VendingMachinePresenter();

        // 4. Instantiate the Console Action
        $this->action = new StatusAction($queryHandler, $presenter);
    }

    public function testItSupportsStatusToken(): void
    {
        self::assertTrue($this->action->supports('STATUS'));
        self::assertFalse($this->action->supports('STATUS-NOW'));
    }

    public function testItQueriesStateAndDelegatesToPresenter(): void
    {
        // 1. Instantiate the REAL VendingMachine (fresh and empty)
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $realMachine = new VendingMachine($policy);

        // 2. The query handler will fetch this machine from the repository
        $this->repositoryMock->expects(self::once())
            ->method('get')
            ->willReturn($realMachine);

        // 3. Expect the output mock to receive exactly the 6 lines printed by the REAL presenter
        $this->outputMock->expects(self::exactly(6))
            ->method('writeln')
            ->with(self::logicalOr(
                self::stringContains('=== CURRENT MACHINE STATE ==='),
                self::stringContains('Vault (Available Change):'),
                self::stringContains('[Empty]'),
                self::stringContains('Inventory:'),
                self::stringContains('=========================')
            ));

        // 4. Execute the action
        $result = $this->action->execute('STATUS', $this->outputMock);

        // 5. Assert the action returns null (as it delegates directly to output)
        self::assertNull($result);
    }
}