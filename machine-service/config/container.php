<?php

declare(strict_types=1);

use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use App\VendingMachine\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use App\VendingMachine\Infrastructure\Controller\Console\VendingMachineCommand;
use App\VendingMachine\Infrastructure\Controller\Console\Parser\ServicePayloadParser;
use App\VendingMachine\Infrastructure\Controller\Console\Action\InsertCoinAction;
use App\VendingMachine\Infrastructure\Controller\Console\Action\ReturnCoinAction;
use App\VendingMachine\Infrastructure\Controller\Console\Action\ServiceMachineAction;
use App\VendingMachine\Infrastructure\Controller\Console\Action\StatusAction;
use App\VendingMachine\Infrastructure\Controller\Console\Action\VendProductAction;


use DI\ContainerBuilder;
use function DI\autowire;
use function DI\get;

$builder = new ContainerBuilder();

$builder->addDefinitions([

    // Define the regional policy for accepted coins (US context by default).
    // These string representations are used by the CLI actions to parse raw user input safely.
    'app.config.valid_coins' => ['0.05', '0.10', '0.25', '1.00'],

    'app.config.initial_change' => [0.25, 0.25, 0.25, 0.25, 0.25, 0.25, 0.10, 0.10, 0.10, 0.10, 0.10, 0.10, 0.05, 0.05, 0.05, 0.05, 0.05, 0.05 ],
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

    'app.config.machine_id' => 'cli-machine-001',

    // 1. Mapping the Domain Port to the Infrastructure Adapter
    VendingMachineRepositoryInterface::class => autowire(InMemoryVendingMachineRepository::class),

    // 2. Wiring Console Parsers and Actions
    ServicePayloadParser::class => autowire()
        ->constructorParameter('validCoins', get('app.config.valid_coins'))
        ->constructorParameter('productPrices', get('app.config.product_prices')),

    InsertCoinAction::class => autowire()
        ->constructorParameter('validCoins', get('app.config.valid_coins'))
        ->constructorParameter('machineId', get('app.config.machine_id')),

    ReturnCoinAction::class => autowire()
        ->constructorParameter('machineId', get('app.config.machine_id')),

    ServiceMachineAction::class => autowire()
        ->constructorParameter('machineId', get('app.config.machine_id')),

    StatusAction::class => autowire()
        ->constructorParameter('machineId', get('app.config.machine_id')),

    VendProductAction::class => autowire()
        ->constructorParameter('machineId', get('app.config.machine_id')),

    // 3. Wiring the Main Console Command
    VendingMachineCommand::class => autowire()
        ->constructorParameter('actions', [
            get(\App\VendingMachine\Infrastructure\Controller\Console\Action\InsertCoinAction::class),
            get(\App\VendingMachine\Infrastructure\Controller\Console\Action\ReturnCoinAction::class),
            get(\App\VendingMachine\Infrastructure\Controller\Console\Action\VendProductAction::class),
            get(\App\VendingMachine\Infrastructure\Controller\Console\Action\ServiceMachineAction::class),
            get(\App\VendingMachine\Infrastructure\Controller\Console\Action\StatusAction::class),
        ])
        ->constructorParameter('machineId', get('app.config.machine_id'))
        ->constructorParameter('validCoins', get('app.config.valid_coins'))
        ->constructorParameter('initialChangeCoins', get('app.config.initial_change'))
        ->constructorParameter('initialInventory', get('app.config.initial_inventory'))
        ->constructorParameter('productPrices', get('app.config.product_prices')),
]);

return $builder->build();