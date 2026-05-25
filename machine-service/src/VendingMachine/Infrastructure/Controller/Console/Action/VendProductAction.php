<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\VendProductCommand;
use App\VendingMachine\Application\Command\VendProductCommandHandler;
use App\VendingMachine\Infrastructure\Controller\Console\Presenter\VendingMachinePresenter;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class VendProductAction implements ConsoleActionInterface
{
    public function __construct(
        private VendProductCommandHandler $handler,
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
        $result = $this->handler->__invoke(new VendProductCommand($productName));

        $outputStr = $result->product->name;
        $changeStr = $this->presenter->formatCoins($result->change);

        if ($changeStr !== '') {
            $outputStr .= ', ' . $changeStr;
        }

        return $outputStr;
    }
}