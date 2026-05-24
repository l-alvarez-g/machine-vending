<?php

declare(strict_types=1);

use App\VendingMachine\Domain\Repository\VendingMachineRepositoryInterface;
use App\VendingMachine\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use DI\ContainerBuilder;
use function DI\create;

$builder = new ContainerBuilder();

$builder->addDefinitions([
    // Mapping the Domain Port (Interface) to the Infrastructure Adapter (Implementation)
    VendingMachineRepositoryInterface::class => create(InMemoryVendingMachineRepository::class),
]);

return $builder->build();