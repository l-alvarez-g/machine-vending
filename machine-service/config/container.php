<?php

declare(strict_types=1);

use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use App\VendingMachine\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use App\VendingMachine\Infrastructure\Controller\Console\VendingMachineCommand;
use App\VendingMachine\Infrastructure\Controller\Console\Parser\ServicePayloadParser;
use App\VendingMachine\Infrastructure\Controller\Console\Action\InsertCoinAction;

use DI\ContainerBuilder;
use function DI\create;

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



    ServicePayloadParser::class => \DI\autowire()
        ->constructorParameter('validCoins', \DI\get('app.config.valid_coins'))
        ->constructorParameter('productPrices', \DI\get('app.config.product_prices')),

    InsertCoinAction::class => \DI\autowire()
        ->constructorParameter('validCoins', \DI\get('app.config.valid_coins')),

    VendingMachineCommand::class => \DI\autowire()
        ->constructorParameter('actions', [
            \DI\get(\App\VendingMachine\Infrastructure\Controller\Console\Action\InsertCoinAction::class),
            \DI\get(\App\VendingMachine\Infrastructure\Controller\Console\Action\ReturnCoinAction::class),
            \DI\get(\App\VendingMachine\Infrastructure\Controller\Console\Action\VendProductAction::class),
            \DI\get(\App\VendingMachine\Infrastructure\Controller\Console\Action\ServiceMachineAction::class),
            \DI\get(\App\VendingMachine\Infrastructure\Controller\Console\Action\StatusAction::class),
        ])
        ->constructorParameter('initialChangeCoins', \DI\get('app.config.initial_change'))
        ->constructorParameter('initialInventory', \DI\get('app.config.initial_inventory'))
        ->constructorParameter('productPrices', \DI\get('app.config.product_prices')),

    // Mapping the Domain Port (Interface) to the Infrastructure Adapter (Implementation)
    VendingMachineRepositoryInterface::class => create(InMemoryVendingMachineRepository::class),
]);

return $builder->build();