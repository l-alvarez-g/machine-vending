<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InsufficientFundsException;
use App\VendingMachine\Domain\Exception\OutOfStockException;
use App\VendingMachine\Domain\Exception\ExactChangeNotAvailableException;
use App\VendingMachine\Domain\Event\MachineServicedEvent;
use App\Shared\Domain\Event\DomainEvent;

final class VendingMachine
{
    private MoneyCollection $insertedMoney;
    private MoneyCollection $vault;

    /** @var array<string, array{product: Product, quantity: int}> */
    private array $inventory = [];

    /** @var list<DomainEvent> List of Domain Events recorded by the Aggregate */
    private array $domainEvents = [];

    public function __construct(
        private readonly string $id, // Identity is mandatory for an Aggregate Root
        private readonly AcceptedCoinsPolicy $acceptedCoinsPolicy
    ) {
        $this->insertedMoney = MoneyCollection::empty();
        $this->vault = MoneyCollection::empty();
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * @param array<string, array{product: Product, quantity: int}> $inventory
     */
    public function serviceMachine(MoneyCollection $initialChange, array $inventory): void
    {
        $this->vault = $initialChange;
        $this->inventory = $inventory;

        $this->record(new MachineServicedEvent($this->id));
    }

    public function insertCoin(Coin $coin): void
    {
        $this->acceptedCoinsPolicy->assertIsSatisfiedBy($coin);
        $this->insertedMoney = $this->insertedMoney->add($coin);
    }

    public function returnCoins(): MoneyCollection
    {
        $returned = $this->insertedMoney;
        $this->insertedMoney = MoneyCollection::empty();

        return $returned;
    }

    public function vendProduct(string $productName): VendResult
    {
        if (!isset($this->inventory[$productName]) || $this->inventory[$productName]['quantity'] <= 0) {
            throw OutOfStockException::forProduct($productName);
        }

        $product = $this->inventory[$productName]['product'];
        $insertedTotal = $this->insertedMoney->totalInCents();

        if ($insertedTotal < $product->priceInCents()) {
            throw InsufficientFundsException::forTransaction($insertedTotal, $product->priceInCents());
        }

        $changeAmount = $insertedTotal - $product->priceInCents();
        $changeToReturn = $this->calculateChange($changeAmount);

        // Update state
        $this->inventory[$productName]['quantity']--;
        $this->vault = $this->vaultAdd($this->insertedMoney);
        $this->vault = $this->vaultSubtract($changeToReturn);
        $this->insertedMoney = MoneyCollection::empty();

        // CQRS / Event Sourcing foundation: Record that something important happened in the business
        // $this->record(new ProductVendedEvent($this->id, $productName, $product->priceInCents()));

        return new VendResult($product, $changeToReturn);
    }

    private function calculateChange(int $changeNeededInCents): MoneyCollection
    {
        if ($changeNeededInCents === 0) {
            return MoneyCollection::empty();
        }

        $changeCoins = [];
        $remaining = $changeNeededInCents;

        $availableCoins = $this->vault->coins();
        usort($availableCoins, static fn(Coin $a, Coin $b): int => $b->amountInCents() <=> $a->amountInCents());

        foreach ($availableCoins as $coin) {
            if ($remaining >= $coin->amountInCents()) {
                $changeCoins[] = $coin;
                $remaining -= $coin->amountInCents();
            }
        }

        if ($remaining > 0) {
            throw ExactChangeNotAvailableException::forAmount($remaining);
        }

        return new MoneyCollection(...$changeCoins);
    }

    private function vaultAdd(MoneyCollection $collection): MoneyCollection
    {
        $newVault = $this->vault;
        foreach ($collection->coins() as $coin) {
            $newVault = $newVault->add($coin);
        }
        return $newVault;
    }

    private function vaultSubtract(MoneyCollection $collectionToSubtract): MoneyCollection
    {
        $currentVaultCoins = $this->vault->coins();

        foreach ($collectionToSubtract->coins() as $coinToRemove) {
            foreach ($currentVaultCoins as $index => $vaultCoin) {
                if ($vaultCoin->amountInCents() === $coinToRemove->amountInCents()) {
                    unset($currentVaultCoins[$index]);
                    break; // Break inner loop only
                }
            }
        }

        return new MoneyCollection(...array_values($currentVaultCoins));
    }

    /**
     * @return array<string, array{product: Product, quantity: int}>
     */
    public function inventory(): array
    {
        return $this->inventory;
    }

    public function vault(): MoneyCollection
    {
        return $this->vault;
    }

    /**
     * Records a domain event to be dispatched later by the application layer or infrastructure.
     */
    private function record(DomainEvent $domainEvent): void
    {
        $this->domainEvents[] = $domainEvent;
    }

    /**
     * Retrieves and clears the recorded domain events.
     * @return list<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}