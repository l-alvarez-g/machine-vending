<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\InsertCoinCommand;
use App\VendingMachine\Application\Command\InsertCoinCommandHandler;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class InsertCoinAction implements ConsoleActionInterface
{
    /** @var array<int, float> */
    private array $validCoinsFloat;

    /**
     * @param InsertCoinCommandHandler $handler
     * @param array<int, string> $validCoins Array of raw config strings (e.g. ['0.05', '1.00'])
     * @param string $machineId The fixed ID for the CLI machine instance
     */
    public function __construct(
        private InsertCoinCommandHandler $handler,
        private array $validCoins,
        private string $machineId
    ) {
        // Pre-compute float values once for performance
        $this->validCoinsFloat = array_map('floatval', $this->validCoins);
    }

    public function supports(string $tokenUpper): bool
    {
        return is_numeric($tokenUpper);
    }

    public function execute(string $tokenUpper, OutputInterface $output): ?string
    {
        $coinValue = (float) $tokenUpper;

        // Early return/filter to avoid unnecessary domain hydration for obvious garbage
        if (in_array($coinValue, $this->validCoinsFloat, true)) {
            // Dispatch the Command with the proper Aggregate Identity
            $this->handler->__invoke(new InsertCoinCommand($this->machineId, $coinValue));
            return null;
        }

        $output->writeln(sprintf('<error>Invalid coin value: %s</error>', $tokenUpper));
        return null;
    }
}