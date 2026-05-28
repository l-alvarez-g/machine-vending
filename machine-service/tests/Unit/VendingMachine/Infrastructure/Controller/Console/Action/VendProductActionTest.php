<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\ReturnCoinsCommandHandler;
use App\VendingMachine\Application\Command\VendProductCommandHandler;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\MoneyCollection;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Model\Product;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use App\VendingMachine\Infrastructure\Controller\Console\Action\VendProductAction;
use App\VendingMachine\Infrastructure\Controller\Console\Presenter\VendingMachinePresenter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

final class VendProductActionTest extends TestCase
{
    private VendingMachineRepositoryInterface&MockObject $repositoryMock;
    private OutputInterface&MockObject $outputMock;
    private VendProductAction $action;
    private const MACHINE_ID = 'cli-machine-001';

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        $handler = new VendProductCommandHandler($this->repositoryMock);
        $returnHandler = new ReturnCoinsCommandHandler($this->repositoryMock);
        $presenter = new VendingMachinePresenter();

        $this->action = new VendProductAction($handler, $returnHandler, $presenter, self::MACHINE_ID);
    }

    public function testItExecutesVendAndFormatsResultWithExactChange(): void
    {
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $realMachine = new VendingMachine(self::MACHINE_ID, $policy);

        // A. Prepare inventory and initial change
        // We need to pass MoneyCollection objects to the serviceMachine method
        $initialChange = new MoneyCollection(new Coin(25), new Coin(10));

        $soda = new Product('SODA', 165);

        $realMachine->serviceMachine(
            $initialChange, 
            [
                'SODA' => [
                    'product'  => $soda, // This key MUST match $this->inventory[$productName]['product']
                    'quantity' => 5
                ]
            ]
        );

        // B. User inserts 2.00 (1.00 + 1.00)
        $realMachine->insertCoin(new Coin(100));
        $realMachine->insertCoin(new Coin(100));

        // C. The repository returns the machine by its ID
        $this->repositoryMock->expects(self::once())
            ->method('get')
            ->with(self::MACHINE_ID)
            ->willReturn($realMachine);

        $this->repositoryMock->expects(self::once())
            ->method('save')
            ->with(self::identicalTo($realMachine));

        // D. Execute (Price 1.65, inserted 2.00, change 0.35 => should return 0.25, 0.10)
        $result = $this->action->execute('GET-SODA', $this->outputMock);

        // E. Validate exact format: "Product, Returned: 0.25, 0.10"
        self::assertSame('SODA, 0.25, 0.10', $result);
    }

}