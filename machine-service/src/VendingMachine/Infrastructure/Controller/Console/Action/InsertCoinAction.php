<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\InsertCoinCommand;
use App\VendingMachine\Application\Command\InsertCoinCommandHandler;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class InsertCoinAction implements ConsoleActionInterface
{
    /**
     * @param array<int, string|float> $validCoins
     */
    public function __construct(
        private InsertCoinCommandHandler $handler,
        private array $validCoins
    ) {
    }

    public function supports(string $tokenUpper): bool
    {
        return is_numeric($tokenUpper);
    }

    public function execute(string $tokenUpper, OutputInterface $output): ?string
    {
        $validCoinsFloat = array_map('floatval', $this->validCoins);

        if (in_array((float) $tokenUpper, $validCoinsFloat, true)) {
            $this->handler->__invoke(new InsertCoinCommand((float) $tokenUpper));
            return null;
        }

        $output->writeln(sprintf('<error>Invalid coin value: %s</error>', $tokenUpper));
        return null;
    }
}