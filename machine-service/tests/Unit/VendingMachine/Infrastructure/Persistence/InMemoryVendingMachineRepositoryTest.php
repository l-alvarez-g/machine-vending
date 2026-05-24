<?php

declare(strict_types=1);

namespace App\Tests\Integration\VendingMachine\Infrastructure\Persistence;

use App\VendingMachine\Domain\Model\Coin;
use App\VendingMachine\Domain\Model\VendingMachine;
use App\VendingMachine\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use PHPUnit\Framework\TestCase;

final class InMemoryVendingMachineRepositoryTest extends TestCase
{
    public function testItSavesAndRetrievesTheSameInstance(): void
    {
        $repository = new InMemoryVendingMachineRepository();

        $machine = $repository->get();
        $machine->insertCoin(new Coin(1.00));

        $repository->save($machine);

        $retrievedMachine = $repository->get();

        $this->assertSame(
            100,
            $retrievedMachine->returnCoins()->totalInCents(),
            'The repository must return the exact state that was saved.'
        );
    }
}