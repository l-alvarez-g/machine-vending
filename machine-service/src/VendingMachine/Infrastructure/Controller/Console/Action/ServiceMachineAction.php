<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Command\ServiceMachineCommand;
use App\VendingMachine\Application\Command\ServiceMachineCommandHandler;
use App\VendingMachine\Application\Query\GetMachineStateQuery;
use App\VendingMachine\Application\Query\GetMachineStateQueryHandler;
use App\VendingMachine\Infrastructure\Controller\Console\Parser\ServicePayloadParser;
use App\VendingMachine\Infrastructure\Controller\Console\Presenter\VendingMachinePresenter;
use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ServiceMachineAction implements ConsoleActionInterface
{
    /**
     * @param string $machineId Injected via PHP-DI container config
     */
    public function __construct(
        private ServiceMachineCommandHandler $commandHandler,
        private GetMachineStateQueryHandler $queryHandler,
        private ServicePayloadParser $parser,
        private VendingMachinePresenter $presenter,
        private string $machineId
    ) {
    }

    public function supports(string $tokenUpper): bool
    {
        return (bool) preg_match('/^SERVICE\[(.*)\]$/', $tokenUpper);
    }

    public function execute(string $tokenUpper, OutputInterface $output): ?string
    {
        if (preg_match('/^SERVICE\[(.*)\]$/', $tokenUpper, $matches) !== 1) {
            return null;
        }

        $payload = $matches[1];

        try {
            // 1. Fetch current state passing the explicit machine identity
            $currentState = $this->queryHandler->__invoke(new GetMachineStateQuery($this->machineId));

            // 2. Parse the CLI payload
            $parsedData = $this->parser->parse($payload, $currentState);

            // 3. Dispatch the command with the identity
            $this->commandHandler->__invoke(new ServiceMachineCommand(
                $this->machineId,
                $parsedData['coins'], 
                $parsedData['inventory']
            ));

            $output->writeln('<comment>Machine Serviced successfully.</comment>');

            // 4. Fetch the new state and present it
            $newState = $this->queryHandler->__invoke(new GetMachineStateQuery($this->machineId));
            $this->presenter->displayStatus($output, 'STATE AFTER SERVICE', $newState);

        } catch (InvalidArgumentException $exception) {
            $output->writeln(sprintf('<error>Service aborted. %s</error>', $exception->getMessage()));
        }

        return null;
    }
}