<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console;

use App\VendingMachine\Application\Command\ServiceMachineCommand;
use App\VendingMachine\Application\Command\ServiceMachineCommandHandler;
use App\VendingMachine\Infrastructure\Controller\Console\Action\ConsoleActionInterface;
use DomainException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class VendingMachineCommand extends Command
{
    /**
     * @param iterable<ConsoleActionInterface> $actions Array of registered strategy handlers
     * @param array<int, float> $initialChangeCoins
     * @param array<string, array{quantity: int}> $initialInventory
     * @param array<string, float> $productPrices
     */
    public function __construct(
        private readonly iterable $actions,
        private readonly ServiceMachineCommandHandler $serviceMachineHandler,
        private readonly array $initialChangeCoins,
        private readonly array $initialInventory,
        private readonly array $productPrices
    ) {
        parent::__construct('app:vending-machine');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Hydrate initial inventory and boot the machine
        $hydratedInventory = [];
        foreach ($this->initialInventory as $itemName => $data) {
            if (isset($this->productPrices[$itemName])) {
                $hydratedInventory[$itemName] = [
                    'price' => $this->productPrices[$itemName],
                    'quantity' => $data['quantity']
                ];
            }
        }

        $this->serviceMachineHandler->__invoke(new ServiceMachineCommand(
            $this->initialChangeCoins,
            $hydratedInventory
        ));

        $output->writeln('<info>Vending Machine Ready. Type EXIT to quit.</info>');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

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
                $handled = false;

                // Strategy Pattern Execution: Delegate to the first action that supports the token
                foreach ($this->actions as $action) {
                    if ($action->supports($tokenUpper)) {
                        $response = $action->execute($tokenUpper, $output);
                        if ($response !== null) {
                            $responses[] = $response;
                        }
                        $handled = true;
                        break;
                    }
                }

                if (!$handled) {
                    $output->writeln(sprintf('<error>Unknown command: %s</error>', $token));
                }
            }

            // Print accumulated responses (e.g., returned items)
            $responses = array_filter($responses);
            if ($responses !== []) {
                $output->writeln(sprintf('-> %s', implode("\n-> ", $responses)));
            }

        } catch (DomainException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
        }
    }
}