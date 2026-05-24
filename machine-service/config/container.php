<?php

declare(strict_types=1);

use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use App\VendingMachine\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use App\VendingMachine\Infrastructure\Controller\Console\VendingMachineCommand;
use DI\ContainerBuilder;
use function DI\create;
use function DI\autowire;
use function DI\get;

$builder = new ContainerBuilder();

$builder->addDefinitions([

    // Define the regional policy for accepted coins (US context by default)
    'app.config.valid_coins' => ['0.05', '0.10', '0.25', '1', '1.0', '1.00'],

    'app.config.initial_change' => [0.25, 0.25, 0.10, 0.10, 0.05, 0.05],
    'app.config.initial_inventory' => [
        'WATER' => ['price' => 0.65, 'quantity' => 10],
        'JUICE' => ['price' => 1.00, 'quantity' => 10],
        'SODA'  => ['price' => 1.50, 'quantity' => 10],
    ],

    VendingMachineCommand::class => autowire(VendingMachineCommand::class)
        ->constructorParameter('validCoins', get('app.config.valid_coins'))
        ->constructorParameter('initialChangeCoins', \DI\get('app.config.initial_change'))
        ->constructorParameter('initialInventory', \DI\get('app.config.initial_inventory')),

    // Mapping the Domain Port (Interface) to the Infrastructure Adapter (Implementation)
    VendingMachineRepositoryInterface::class => create(InMemoryVendingMachineRepository::class),
]);

return $builder->build();