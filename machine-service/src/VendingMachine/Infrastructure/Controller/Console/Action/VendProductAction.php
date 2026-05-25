<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\ReturnCoinsCommandHandler;
use App\VendingMachine\Application\Command\ReturnCoinsCommand;
use App\VendingMachine\Application\Command\VendProductCommand;
use App\VendingMachine\Application\Command\VendProductCommandHandler;
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
        private VendingMachinePresenter $presenter
    ) {
    }

    public function supports(string $tokenUpper): bool
    {
        return str_starts_with($tokenUpper, 'GET-');
    }

    // Covariance: Narrowing the return type to string because it never returns null
    public function execute(string $tokenUpper, OutputInterface $output): string
    {
        $productName = str_replace('GET-', '', $tokenUpper);

        try {
            // 1. Try to execute the main Use Case
            $result = $this->vendHandler->__invoke(new VendProductCommand($productName));

            $outputStr = $result->product->name;
            $changeStr = $this->presenter->formatCoins($result->change);

            if ($changeStr !== '') {
                $outputStr .= ', ' . $changeStr;
            }

            return $outputStr;

        } catch (ExactChangeNotAvailableException | OutOfStockException $e) {
            // 2. Print the business error
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            // 3. Compensating Action: Execute the Return Coins Use Case Compensating Transaction
            $returnedCoins = $this->returnHandler->__invoke(new ReturnCoinsCommand());

            return $this->presenter->formatCoins($returnedCoins);

        } catch (DomainException $e) {
            // InsufficientFundsException falls here
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return '';
        }
    }
}