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
     */
    public function __construct(
        private readonly InsertCoinCommandHandler $insertCoinHandler,
        private readonly VendProductCommandHandler $vendProductHandler,
        private readonly ReturnCoinsCommandHandler $returnCoinsHandler,
        private readonly ServiceMachineCommandHandler $serviceMachineHandler,
        private readonly array $validCoins
    ) {
        parent::__construct('app:vending-machine');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Initializing the machine with some change and inventory for the session
        $this->serviceMachineHandler->__invoke(new ServiceMachineCommand(
            initialChangeCoins: [0.25, 0.25, 0.10, 0.10, 0.05, 0.05],
            inventory: [
                'WATER' => ['price' => 0.65, 'quantity' => 10],
                'JUICE' => ['price' => 1.00, 'quantity' => 10],
                'SODA'  => ['price' => 1.50, 'quantity' => 10],
            ]
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
                } elseif ($tokenUpper === 'SERVICE') {
                    $this->serviceMachineHandler->__invoke(new ServiceMachineCommand(
                        [0.25, 0.25, 0.10, 0.10, 0.05],
                        [
                            'WATER' => ['price' => 0.65, 'quantity' => 10], 
                            'SODA'  => ['price' => 1.50, 'quantity' => 10], 
                            'JUICE' => ['price' => 1.00, 'quantity' => 10]
                        ]
                    ));
                    $output->writeln('<comment>Machine Serviced.</comment>');
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