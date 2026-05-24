<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console;

use App\VendingMachine\Application\Command\InsertCoinCommand;
use App\VendingMachine\Application\Command\InsertCoinCommandHandler;
use App\VendingMachine\Application\Command\ReturnCoinsCommand;
use App\VendingMachine\Application\Command\ReturnCoinsCommandHandler;
use App\VendingMachine\Application\Command\ServiceMachineCommand;
use App\VendingMachine\Application\Command\ServiceMachineCommandHandler;
use App\VendingMachine\Application\Command\VendProductCommand;
use App\VendingMachine\Application\Command\VendProductCommandHandler;
use App\VendingMachine\Application\Query\GetMachineStateQuery;
use App\VendingMachine\Application\Query\GetMachineStateQueryHandler;
use App\VendingMachine\Application\Query\MachineStateDTO;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\MoneyCollection;
use DomainException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class VendingMachineCommand extends Command
{
    /**
     * @param array<int, string> $validCoins
     * @param array<int, float> $initialChangeCoins
     * @param array<string, array{quantity: int}> $initialInventory
     * @param array<string, float> $productPrices
     */
    public function __construct(
        private readonly InsertCoinCommandHandler $insertCoinHandler,
        private readonly VendProductCommandHandler $vendProductHandler,
        private readonly ReturnCoinsCommandHandler $returnCoinsHandler,
        private readonly ServiceMachineCommandHandler $serviceMachineHandler,
        private readonly GetMachineStateQueryHandler $getMachineStateHandler,
        private readonly array $validCoins,
        private readonly array $initialChangeCoins,
        private readonly array $initialInventory,
        private readonly array $productPrices
    ) {
        parent::__construct('app:vending-machine');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Hydrate initial inventory with prices from the catalog
        $hydratedInventory = [];
        foreach ($this->initialInventory as $itemName => $data) {
            if (isset($this->productPrices[$itemName])) {
                $hydratedInventory[$itemName] = [
                    'price' => $this->productPrices[$itemName],
                    'quantity' => $data['quantity']
                ];
            }
        }

        // 2. Initialize the machine
        $this->serviceMachineHandler->__invoke(new ServiceMachineCommand(
            $this->initialChangeCoins,
            $hydratedInventory
        ));

        $output->writeln('<info>Vending Machine Ready. Type EXIT to quit.</info>');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        // 3. REPL Loop
        while (true) {
            $question = new Question('> ');

            /** @var mixed $answer */
            $answer = $helper->ask($input, $output, $question);

            if ($answer === null) {
                break;
            }

            if (!is_string($answer)) {
                $output->writeln('<error>Invalid input. Expected string.</error>');
                continue;
            }

            $inputString = trim($answer);

            if (strtoupper($inputString) === 'EXIT') {
                break;
            }

            if ($inputString !== '') {
                $this->processInput($inputString, $output);
            }
        }

        return Command::SUCCESS;
    }

    private function processInput(string $inputStr, OutputInterface $output): void
    {
        $tokens = array_map('trim', explode(',', $inputStr));
        $responses = [];

        try {
            foreach ($tokens as $token) {
                $tokenUpper = strtoupper($token);

                // --- The Router: Clean, intention-revealing delegation ---
                if (is_numeric($token)) {
                    $this->handleInsertCoin($token, $output);
                } elseif ($tokenUpper === 'RETURN-COIN') {
                    $responses[] = $this->handleReturnCoin();
                } elseif (str_starts_with($tokenUpper, 'GET-')) {
                    $responses[] = $this->handleVendProduct($tokenUpper);
                } elseif (preg_match('/^SERVICE\[(.*)\]$/', $tokenUpper, $matches)) {
                    $this->handleServiceMachine($matches[1], $output);
                } elseif ($tokenUpper === 'STATUS') {
                    $this->displayStatus($output, 'CURRENT MACHINE STATE');
                } else {
                    $output->writeln(sprintf('<error>Unknown command: %s</error>', $token));
                }
            }

            // Print accumulated responses (like vended products and change)
            $responses = array_filter($responses); // Remove empty responses
            if ($responses !== []) {
                $output->writeln(sprintf('-> %s', implode("\n-> ", $responses)));
            }

        } catch (DomainException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
        }
    }

    // =========================================================================
    // ACTION HANDLERS
    // =========================================================================

    private function handleInsertCoin(string $token, OutputInterface $output): void
    {
        if (in_array($token, $this->validCoins, true)) {
            $this->insertCoinHandler->__invoke(new InsertCoinCommand((float) $token));
            return;
        }

        $output->writeln(sprintf('<error>Invalid coin value: %s</error>', $token));
    }

    private function handleReturnCoin(): string
    {
        $returnedCoins = $this->returnCoinsHandler->__invoke(new ReturnCoinsCommand());
        return $this->formatCoins($returnedCoins);
    }

    private function handleVendProduct(string $tokenUpper): string
    {
        $productName = str_replace('GET-', '', $tokenUpper);
        $result = $this->vendProductHandler->__invoke(new VendProductCommand($productName));

        $outputStr = $result->product->name;
        $changeStr = $this->formatCoins($result->change);

        if ($changeStr !== '') {
            $outputStr .= ', ' . $changeStr;
        }

        return $outputStr;
    }

    private function handleServiceMachine(string $payload, OutputInterface $output): void
    {
        $coinsPayload = '';
        $inventoryPayload = '';

        // Smart routing for payload
        if (str_contains($payload, '|')) {
            [$coinsPayload, $inventoryPayload] = explode('|', $payload, 2);
        } else {
            if (preg_match('/^[A-Z]/', $payload)) {
                $inventoryPayload = $payload;
            } else {
                $coinsPayload = $payload;
            }
        }

        $currentState = $this->getMachineStateHandler->__invoke(new GetMachineStateQuery());

        $coinsToAdd = $this->parseServiceCoins($coinsPayload, $currentState, $output);
        if ($coinsToAdd === null) {
            return; // Abort if parsing failed
        }

        $inventoryData = $this->parseServiceInventory($inventoryPayload, $currentState, $output);
        if ($inventoryData === null) {
            return; // Abort if parsing failed
        }

        $this->serviceMachineHandler->__invoke(new ServiceMachineCommand($coinsToAdd, $inventoryData));

        $output->writeln('<comment>Machine Serviced successfully.</comment>');
        $this->displayStatus($output, 'STATE AFTER SERVICE');
    }

    // =========================================================================
    // PAYLOAD PARSERS
    // =========================================================================

    /**
     * @return array<int, float>|null Returns null on validation error
     */
    private function parseServiceCoins(string $payload, MachineStateDTO $currentState, OutputInterface $output): ?array
    {
        $coinsToAdd = [];

        if ($payload === '') {
            // Preserve current coins
            foreach ($currentState->vaultCoins as $valStr => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $coinsToAdd[] = (float) $valStr;
                }
            }
            return $coinsToAdd;
        }

        $validCoinsFloat = array_map('floatval', $this->validCoins);
        $coinEntries = explode(';', $payload);

        foreach ($coinEntries as $entry) {
            $entryParts = explode(':', $entry);
            if (count($entryParts) === 2 && is_numeric($entryParts[0]) && is_numeric($entryParts[1])) {
                $coinValue = (float) $entryParts[0];
                $qty = (int) $entryParts[1];

                if (!in_array($coinValue, $validCoinsFloat, true)) {
                    $output->writeln(sprintf('<error>Service aborted. Invalid coin detected: %s</error>', $entryParts[0]));
                    return null;
                }

                for ($i = 0; $i < $qty; $i++) {
                    $coinsToAdd[] = $coinValue;
                }
            }
        }

        return $coinsToAdd;
    }

    /**
     * @return array<string, array{price: float, quantity: int}>|null Returns null on validation error
     */
    private function parseServiceInventory(string $payload, MachineStateDTO $currentState, OutputInterface $output): ?array
    {
        if ($payload === '') {
            return $currentState->inventory;
        }

        $inventoryData = [];
        $itemEntries = explode(';', $payload);

        foreach ($itemEntries as $entry) {
            $entryParts = explode(':', $entry);
            if (count($entryParts) === 2 && is_numeric($entryParts[1])) {
                $itemName = $entryParts[0]; 
                $qty = (int) $entryParts[1];

                if (!isset($this->productPrices[$itemName])) {
                    $output->writeln(sprintf('<error>Service aborted. Unknown product: %s</error>', $itemName));
                    return null;
                }

                $inventoryData[$itemName] = [
                    'price' => $this->productPrices[$itemName],
                    'quantity' => $qty
                ];
            }
        }

        return $inventoryData;
    }

    // =========================================================================
    // RENDER HELPERS
    // =========================================================================

    private function displayStatus(OutputInterface $output, string $title): void
    {
        $state = $this->getMachineStateHandler->__invoke(new GetMachineStateQuery());
        $this->printMachineState($output, $title, $state);
    }

    private function printMachineState(OutputInterface $output, string $title, MachineStateDTO $state): void
    {
        $output->writeln(sprintf("\n<info>=== %s ===</info>", $title));

        $output->writeln('<comment>Vault (Available Change):</comment>');
        if ($state->vaultCoins === []) {
            $output->writeln('  [Empty]');
        } else {
            foreach ($state->vaultCoins as $val => $count) {
                $output->writeln(sprintf('  $%s : %d units', $val, $count));
            }
        }

        $output->writeln("\n<comment>Inventory:</comment>");
        if ($state->inventory === []) {
            $output->writeln('  [Empty]');
        } else {
            foreach ($state->inventory as $name => $data) {
                $output->writeln(sprintf('  %s : %d units ($%.2f)', $name, $data['quantity'], $data['price']));
            }
        }
        $output->writeln("<info>=========================</info>\n");
    }

    private function formatCoins(MoneyCollection $collection): string
    {
        $coins = $collection->coins();
        if ($coins === []) {
            return '';
        }

        $formatted = array_map(
            static fn (Coin $coin) => $coin->amount() == 1.0 ? '1' : number_format($coin->amount(), 2, '.', ''),
            $coins
        );

        return implode(', ', $formatted);
    }
}