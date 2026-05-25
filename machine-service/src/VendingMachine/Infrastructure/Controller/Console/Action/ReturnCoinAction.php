<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\ReturnCoinsCommand;
use App\VendingMachine\Application\Command\ReturnCoinsCommandHandler;
use App\VendingMachine\Infrastructure\Controller\Console\Presenter\VendingMachinePresenter;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ReturnCoinAction implements ConsoleActionInterface
{
    public function __construct(
        private ReturnCoinsCommandHandler $handler,
        private VendingMachinePresenter $presenter
    ) {
    }

    public function supports(string $tokenUpper): bool
    {
        return $tokenUpper === 'RETURN-COIN';
    }

    // Covariance: Narrowing the return type to string because it never returns null
    public function execute(string $tokenUpper, OutputInterface $output): string
    {
        $returnedCoins = $this->handler->__invoke(new ReturnCoinsCommand());

        return $this->presenter->formatCoins($returnedCoins);
    }
}