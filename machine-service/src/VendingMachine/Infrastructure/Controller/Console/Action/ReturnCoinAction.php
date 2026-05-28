<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\ReturnCoinsCommand;
use App\VendingMachine\Application\Command\ReturnCoinsCommandHandler;
use App\VendingMachine\Application\Command\ReturnCoinsResponse;
use App\VendingMachine\Infrastructure\Controller\Console\Presenter\VendingMachinePresenter;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ReturnCoinAction implements ConsoleActionInterface
{
    /**
     * @param string $machineId Injected via PHP-DI container config
     */
    public function __construct(
        private ReturnCoinsCommandHandler $handler,
        private VendingMachinePresenter $presenter,
        private string $machineId
    ) {
    }

    public function supports(string $tokenUpper): bool
    {
        return $tokenUpper === 'RETURN-COIN';
    }

    // Covariance: Narrowing the return type to string because it never returns null
    public function execute(string $tokenUpper, OutputInterface $output): string
    {
        // 1. Dispatch the Command with the proper Aggregate Identity
        /** @var ReturnCoinsResponse $response */
        $response = $this->handler->__invoke(new ReturnCoinsCommand($this->machineId));

        // 2. Delegate formatting to the Presentation layer
        return $this->presenter->formatReturnedCoins($response);
    }
}