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
        'WATER' => ['quantity' => 10],
        'JUICE' => ['quantity' => 10],
        'SODA'  => ['quantity' => 10],
    ],
    'app.config.product_prices' => [
        'WATER' => 0.65,
        'JUICE' => 1.00,
        'SODA'  => 1.50,
    ],



    VendingMachineCommand::class => autowire(VendingMachineCommand::class)
        ->constructorParameter('validCoins', get('app.config.valid_coins'))
        ->constructorParameter('initialChangeCoins', \DI\get('app.config.initial_change'))
        ->constructorParameter('initialInventory', \DI\get('app.config.initial_inventory'))
        ->constructorParameter('productPrices', \DI\get('app.config.product_prices')),

    // Mapping the Domain Port (Interface) to the Infrastructure Adapter (Implementation)
    VendingMachineRepositoryInterface::class => create(InMemoryVendingMachineRepository::class),
]);

return $builder->build();