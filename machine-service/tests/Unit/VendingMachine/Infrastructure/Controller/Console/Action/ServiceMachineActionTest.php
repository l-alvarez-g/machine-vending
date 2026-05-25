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

    protected function setUp(): void
    {
        // 1. Mock the Infrastructure Port (Interfaces can always be mocked)
        $this->repositoryMock = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        // 2. Instantiate the REAL Application layer handlers
        $commandHandler = new ServiceMachineCommandHandler($this->repositoryMock);
        $queryHandler = new GetMachineStateQueryHandler($this->repositoryMock);

        // 3. Instantiate the REAL Parser with dummy configuration
        $validCoins = ['0.05', '0.10', '0.25', '1'];
        $productPrices = ['SODA' => 1.50];
        $parser = new ServicePayloadParser($validCoins, $productPrices);

        // 4. Instantiate the REAL Presenter
        $presenter = new VendingMachinePresenter();

        // 5. Instantiate the Console Action
        $this->action = new ServiceMachineAction(
            $commandHandler,
            $queryHandler,
            $parser,
            $presenter
        );
    }

    public function testItSupportsServiceToken(): void
    {
        self::assertTrue($this->action->supports('SERVICE[1:1|SODA:10]'));
        self::assertFalse($this->action->supports('SERVICE'));
    }

    public function testItIgnoresMalformedRegexMatches(): void
    {
        $result = $this->action->execute('INVALID-FORMAT', $this->outputMock);

        self::assertNull($result);
    }

    public function testItCatchesParserExceptionsAndPrintsError(): void
    {
        // 1. Instantiate the REAL VendingMachine so the QueryHandler can map it to a DTO
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $realMachine = new VendingMachine($policy);

        // 2. The query handler will fetch this machine before parsing
        $this->repositoryMock->expects(self::once())
            ->method('get')
            ->willReturn($realMachine);

        // 3. Ensure the repository is NEVER saved (protecting the state from bad payloads)
        $this->repositoryMock->expects(self::never())->method('save');

        // 4. Ensure the console prints the graceful error message.
        // The REAL parser will naturally throw this because 'CHORIZO' is not in $productPrices.
        $this->outputMock->expects(self::once())
            ->method('writeln')
            ->with('<error>Service aborted. Unknown product: CHORIZO</error>');

        // 5. Execute the action with an unknown product
        $result = $this->action->execute('SERVICE[CHORIZO:1]', $this->outputMock);

        self::assertNull($result);
    }
}