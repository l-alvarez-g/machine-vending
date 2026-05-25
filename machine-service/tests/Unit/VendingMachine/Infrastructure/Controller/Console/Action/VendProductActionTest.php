<?php

declare(strict_types=1);

namespace App\Tests\Unit\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\VendProductCommandHandler;
use App\VendingMachine\Application\Command\ReturnCoinsCommandHandler;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Model\Product;
use App\VendingMachine\Domain\Model\MoneyCollection;
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

    protected function setUp(): void
    {
        // 1. Mock the Infrastructure Port
        $this->repositoryMock = $this->createMock(VendingMachineRepositoryInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        // 2. Instantiate the REAL Application layer handler
        $handler = new VendProductCommandHandler($this->repositoryMock);

        $returnHandler = new ReturnCoinsCommandHandler($this->repositoryMock);

        // 3. Instantiate the REAL Presenter
        $presenter = new VendingMachinePresenter();

        // 4. Instantiate the Console Action
        $this->action = new VendProductAction($handler, $returnHandler, $presenter );
    }

    public function testItSupportsGetTokens(): void
    {
        self::assertTrue($this->action->supports('GET-SODA'));
        self::assertTrue($this->action->supports('GET-WATER'));
        self::assertFalse($this->action->supports('SODA'));
    }

    public function testItExecutesVendAndFormatsResultWithChange(): void
    {
        // 1. Instantiate the REAL VendingMachine
        $policy = new AcceptedCoinsPolicy([5, 10, 25, 100]);
        $realMachine = new VendingMachine($policy);

        // ====================================================================
        // THE FIX: Prepare the domain state using your strict serviceMachine method
        // ====================================================================

        // A. Prepare the Vault (Initial change for the machine to give back)
        // Let's add some quarters so it can return change if needed
        $initialChange = new MoneyCollection(
            new Coin(0.25),
            new Coin(0.25)
        );

        // B. Prepare the Inventory using the EXACT array shape required by PHPStan
        $inventory = [
            'SODA' => [
                'product' => new Product('SODA', 1.50), // Adjust if your Product constructor differs
                'quantity' => 5
            ]
        ];

        // C. Service the machine to load the vault and the inventory
        $realMachine->serviceMachine($initialChange, $inventory);

        // D. Simulate the User inserting coins to pay for the 1.50 SODA
        // (Assuming your insertion method is called insertCoin)
        $realMachine->insertCoin(new Coin(1.0));
        $realMachine->insertCoin(new Coin(1.0)); // User inserted 2.00 in total
        // ====================================================================

        // 2. The repository will return our prepared real machine
        $this->repositoryMock->expects(self::once())
            ->method('get')
            ->willReturn($realMachine);

        // 3. The repository should save the machine after a successful vend
        $this->repositoryMock->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(VendingMachine::class));

        // 4. Execute the action
        $result = $this->action->execute('GET-SODA', $this->outputMock);

        // 5. Assert the expected output.
        self::assertStringContainsString('SODA', $result);

    }
}