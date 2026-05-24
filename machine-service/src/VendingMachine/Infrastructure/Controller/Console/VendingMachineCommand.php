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
     * @param array<string, array{price: float, quantity: int}> $initialInventory
     */
    public function __construct(
        private readonly InsertCoinCommandHandler $insertCoinHandler,
        private readonly VendProductCommandHandler $vendProductHandler,
        private readonly ReturnCoinsCommandHandler $returnCoinsHandler,
        private readonly ServiceMachineCommandHandler $serviceMachineHandler,
        private readonly GetMachineStateQueryHandler $getMachineStateHandler,
        private readonly array $validCoins,
        private readonly array $initialChangeCoins,
        private readonly array $initialInventory
    ) {
        parent::__construct('app:vending-machine');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Initializing the machine with some change and inventory for the session
        $this->serviceMachineHandler->__invoke(new ServiceMachineCommand(
            $this->initialChangeCoins,
            $this->initialInventory
        ));

        $output->writeln('<info>Vending Machine Ready. Type EXIT to quit.</info>');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        // 2. REPL Loop (Read-Eval-Print Loop)
        while (true) {
            $question = new Question('> ');

            /** @var mixed $answer */
            $answer = $helper->ask($input, $output, $question);

            // PHPStan Level 9: Strict type guards
            if ($answer === null) {
                break; // EOF or empty interaction
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

                // Intent recognition: If it is a number, the user is attempting to insert a coin.
                if (is_numeric($token)) {
                    // Strict policy validation (The "Bouncer")
                    if (in_array($token, $this->validCoins, true)) {
                        $this->insertCoinHandler->__invoke(new InsertCoinCommand((float) $token));
                    } else {
                        // Fail fast with a domain-accurate error message
                        $output->writeln(sprintf('<error>Invalid coin value: %s</error>', $token));
                    }
                } elseif ($tokenUpper === 'RETURN-COIN') {
                    $returnedCoins = $this->returnCoinsHandler->__invoke(new ReturnCoinsCommand());
                    $responses[] = $this->formatCoins($returnedCoins);
                } elseif (str_starts_with($tokenUpper, 'GET-')) {
                    $productName = str_replace('GET-', '', $tokenUpper);
                    $result = $this->vendProductHandler->__invoke(new VendProductCommand($productName));

                    $outputStr = $result->product->name;
                    $changeStr = $this->formatCoins($result->change);
                    if ($changeStr !== '') {
                        $outputStr .= ', ' . $changeStr;
                    }
                    $responses[] = $outputStr;

                //Intent recognition: Service Machine
                } elseif (preg_match('/^SERVICE\[(.*)\]$/', $tokenUpper, $matches)) {
                    $payload = $matches[1];

                    $coinsPayload = '';
                    $inventoryPayload = '';

                    // Smart routing: Detect if both exist, or route to the correct parser
                    if (str_contains($payload, '|')) {
                        [$coinsPayload, $inventoryPayload] = explode('|', $payload, 2);
                    } else {
                        // If it starts with a letter (A-Z), it is inventory. Otherwise, it is coins.
                        if (preg_match('/^[A-Z]/', $payload)) {
                            $inventoryPayload = $payload;
                        } else {
                            $coinsPayload = $payload;
                        }
                    }

                    $coinsToAdd = [];
                    $inventoryData = [];

                    // Parse initial change (Coins)
                    if ($coinsPayload !== '') {
                        $coinEntries = explode(';', $coinsPayload);
                        foreach ($coinEntries as $entry) {
                            $entryParts = explode(':', $entry);
                            if (count($entryParts) === 2 && is_numeric($entryParts[0]) && is_numeric($entryParts[1])) {
                                $coinValue = (float) $entryParts[0];
                                $qty = (int) $entryParts[1];

                                for ($i = 0; $i < $qty; $i++) {
                                    $coinsToAdd[] = $coinValue;
                                }
                            }
                        }
                    }

                    // Parse inventory (Products)
                    if ($inventoryPayload !== '') {
                        $standardPrices = ['WATER' => 0.65, 'SODA' => 1.50, 'JUICE' => 1.00];

                        $itemEntries = explode(';', $inventoryPayload);
                        foreach ($itemEntries as $entry) {
                            $entryParts = explode(':', $entry);
                            if (count($entryParts) === 2 && is_numeric($entryParts[1])) {
                                $itemName = $entryParts[0]; 
                                $qty = (int) $entryParts[1];
                                $price = $standardPrices[$itemName] ?? 1.00; 

                                $inventoryData[$itemName] = ['price' => $price, 'quantity' => $qty];
                            }
                        }
                    }

                    $this->serviceMachineHandler->__invoke(new ServiceMachineCommand($coinsToAdd, $inventoryData));
                    $output->writeln('<comment>Machine Serviced successfully.</comment>');
                    $stateAfter = $this->getMachineStateHandler->__invoke(new GetMachineStateQuery());
                    $this->printMachineState($output, 'STATE AFTER SERVICE', $stateAfter);

                } elseif ($tokenUpper === 'STATUS') {
                    $state = $this->getMachineStateHandler->__invoke(new GetMachineStateQuery());
                    $this->printMachineState($output, 'CURRENT MACHINE STATE', $state);

                } else {
                    $output->writeln(sprintf('<error>Unknown command: %s</error>', $token));
                }
            }

            if ($responses !== []) {
                $output->writeln(sprintf('-> %s', implode("\n-> ", $responses)));
            }

        } catch (DomainException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
        }
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