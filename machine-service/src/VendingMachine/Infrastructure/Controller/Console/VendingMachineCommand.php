<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console;

use App\VendingMachine\Application\Command\ServiceMachineCommand;
use App\VendingMachine\Application\Command\ServiceMachineCommandHandler;
use App\VendingMachine\Domain\Model\AcceptedCoinsPolicy;
use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
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
     * @param string $machineId The fixed ID for the CLI machine instance
     * @param array<int, string> $validCoins Raw valid coins from config (e.g., ['0.05', '1.00'])
     * @param array<int, float> $initialChangeCoins
     * @param array<string, array{quantity: int}> $initialInventory
     * @param array<string, float> $productPrices
     */
    public function __construct(
        private readonly iterable $actions,
        private readonly ServiceMachineCommandHandler $serviceMachineHandler,
        private readonly VendingMachineRepositoryInterface $repository,
        private readonly string $machineId,
        private readonly array $validCoins,
        private readonly array $initialChangeCoins,
        private readonly array $initialInventory,
        private readonly array $productPrices
    ) {
        parent::__construct('app:vending-machine');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 0. Dynamically build the Domain Policy from raw configuration
        $policyCents = [];
        foreach ($this->validCoins as $coinStr) {
            // Using the Domain Factory ensures safe float-to-cents translation
            $policyCents[] = Coin::fromFloat((float) $coinStr)->amountInCents();
        }

        // Remove duplicates (since config has '1', '1.0', '1.00') and re-index
        $uniquePolicyCents = array_values(array_unique($policyCents));

        // 1. Install the physical machine into the repository
        $policy = new AcceptedCoinsPolicy($uniquePolicyCents);
        $machine = new VendingMachine($this->machineId, $policy);
        $this->repository->save($machine);

        // 2. Hydrate initial inventory payload
        $hydratedInventory = [];
        foreach ($this->initialInventory as $itemName => $data) {
            if (isset($this->productPrices[$itemName])) {
                $hydratedInventory[$itemName] = [
                    'price'    => $this->productPrices[$itemName],
                    'quantity' => $data['quantity']
                ];
            }
        }

        // 3. Service the machine
        $this->serviceMachineHandler->__invoke(new ServiceMachineCommand(
            $this->machineId,
            $this->initialChangeCoins,
            $hydratedInventory
        ));

        $output->writeln('<info>Vending Machine Ready. Type EXIT to quit.</info>');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        // 4. Start the interaction loop
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
            // Our rich Domain Exceptions will be displayed cleanly here
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
        }
    }
}