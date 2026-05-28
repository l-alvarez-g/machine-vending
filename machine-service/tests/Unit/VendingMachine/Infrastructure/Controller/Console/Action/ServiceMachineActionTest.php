<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\ServiceMachineCommandHandler;
use App\VendingMachine\Application\Query\GetMachineStateQueryHandler;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use App\VendingMachine\Infrastructure\Controller\Console\Action\ServiceMachineAction;
use App\VendingMachine\Infrastructure\Controller\Console\Parser\ServicePayloadParser;
use App\VendingMachine\Infrastructure\Controller\Console\Presenter\VendingMachinePresenter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

final class ServiceMachineActionTest extends TestCase
{
    private VendingMachineRepositoryInterface&MockObject $repositoryMock;
    private OutputInterface&MockObject $outputMock;
    private ServiceMachineAction $action;
    private const MACHINE_ID = 'cli-machine-001';

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        $commandHandler = new ServiceMachineCommandHandler($this->repositoryMock);
        $queryHandler = new GetMachineStateQueryHandler($this->repositoryMock);

        $validCoins = ['0.05', '0.10', '0.25', '1.00'];
        $productPrices = ['SODA' => 1.50];
        $parser = new ServicePayloadParser($validCoins, $productPrices);

        $presenter = new VendingMachinePresenter();

        // Inject the Machine ID into the Action
        $this->action = new ServiceMachineAction(
            $commandHandler,
            $queryHandler,
            $parser,
            $presenter,
            self::MACHINE_ID
        );
    }

    public function testItSupportsServiceToken(): void
    {
        self::assertTrue($this->action->supports('SERVICE[1:1|SODA:10]'));
        self::assertFalse($this->action->supports('SERVICE'));
    }

    public function testItCatchesParserExceptionsAndPrintsError(): void
    {
        // 1. Instantiate with the correct ID
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $realMachine = new VendingMachine(self::MACHINE_ID, $policy);

        // 2. The query handler will look for the machine by its specific ID
        $this->repositoryMock->expects(self::once())
            ->method('get')
            ->with(self::MACHINE_ID)
            ->willReturn($realMachine);

        // 3. Protection: the repository must NEVER be saved if parsing fails
        $this->repositoryMock->expects(self::never())->method('save');

        // 4. Verify the business error message
        $this->outputMock->expects(self::once())
            ->method('writeln')
            ->with('<error>Service aborted. Unknown product: CHORIZO</error>');

        $result = $this->action->execute('SERVICE[CHORIZO:1]', $this->outputMock);

        self::assertNull($result);
    }
}