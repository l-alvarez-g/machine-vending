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
    public function __construct(
        private ServiceMachineCommandHandler $commandHandler,
        private GetMachineStateQueryHandler $queryHandler,
        private ServicePayloadParser $parser,
        private VendingMachinePresenter $presenter
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
            $currentState = $this->queryHandler->__invoke(new GetMachineStateQuery());
            $parsedData = $this->parser->parse($payload, $currentState);

            $this->commandHandler->__invoke(new ServiceMachineCommand(
                $parsedData['coins'], 
                $parsedData['inventory']
            ));

            $output->writeln('<comment>Machine Serviced successfully.</comment>');

            $newState = $this->queryHandler->__invoke(new GetMachineStateQuery());
            $this->presenter->displayStatus($output, 'STATE AFTER SERVICE', $newState);

        } catch (InvalidArgumentException $exception) {
            $output->writeln(sprintf('<error>Service aborted. %s</error>', $exception->getMessage()));
        }

        return null;
    }
}