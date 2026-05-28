<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\ReturnCoinsCommand;
use App\VendingMachine\Application\Command\ReturnCoinsCommandHandler;
use App\VendingMachine\Application\Command\ReturnCoinsResponse;
use App\VendingMachine\Application\Command\VendProductCommand;
use App\VendingMachine\Application\Command\VendProductCommandHandler;
use App\VendingMachine\Application\Command\VendProductResponse;
use App\VendingMachine\Domain\Exception\ExactChangeNotAvailableException;
use App\VendingMachine\Domain\Exception\OutOfStockException;
use App\VendingMachine\Infrastructure\Controller\Console\Presenter\VendingMachinePresenter;
use DomainException;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class VendProductAction implements ConsoleActionInterface
{
    public function __construct(
        private VendProductCommandHandler $vendHandler,
        private ReturnCoinsCommandHandler $returnHandler,
        private VendingMachinePresenter $presenter,
        private string $machineId
    ) {
    }

    public function supports(string $tokenUpper): bool
    {
        return str_starts_with($tokenUpper, 'GET-');
    }

    public function execute(string $tokenUpper, OutputInterface $output): string
    {
        $productName = str_replace('GET-', '', $tokenUpper);

        try {
            // 1. Dispatch the Vending command with Identity
            /** @var VendProductResponse $result */
            $result = $this->vendHandler->__invoke(new VendProductCommand($this->machineId, $productName));

            // 2. Use DTO properties
            $outputStr = $result->productName;

            // Map the float change to a formatted string using the Presenter's new logic
            // Note: We create a temporary response object to reuse the presenter logic
            $changeResponse = new ReturnCoinsResponse($result->changeCoins, $result->changeReturned);
            $changeStr = $this->presenter->formatReturnedCoins($changeResponse);

            if ($changeStr !== '') {
                $outputStr .= ', ' . $changeStr;
            }

            return $outputStr;

        } catch (ExactChangeNotAvailableException | OutOfStockException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            // 3. Compensating Action: Return all coins currently in the machine's credit
            /** @var ReturnCoinsResponse $returnedCoins */
            $returnedCoins = $this->returnHandler->__invoke(new ReturnCoinsCommand($this->machineId));

            return $this->presenter->formatReturnedCoins($returnedCoins);

        } catch (DomainException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return '';
        }
    }
}