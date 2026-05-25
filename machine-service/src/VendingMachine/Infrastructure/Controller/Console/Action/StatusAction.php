<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Action;

use App\VendingMachine\Application\Query\GetMachineStateQuery;
use App\VendingMachine\Application\Query\GetMachineStateQueryHandler;
use App\VendingMachine\Infrastructure\Controller\Console\Presenter\VendingMachinePresenter;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class StatusAction implements ConsoleActionInterface
{
    public function __construct(
        private GetMachineStateQueryHandler $queryHandler,
        private VendingMachinePresenter $presenter
    ) {
    }

    public function supports(string $tokenUpper): bool
    {
        return $tokenUpper === 'STATUS';
    }

    public function execute(string $tokenUpper, OutputInterface $output): ?string
    {
        $state = $this->queryHandler->__invoke(new GetMachineStateQuery());
        $this->presenter->displayStatus($output, 'CURRENT MACHINE STATE', $state);

        return null;
    }
}