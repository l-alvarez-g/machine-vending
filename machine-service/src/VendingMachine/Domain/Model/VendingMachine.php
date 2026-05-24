<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InsufficientFundsException;
use App\VendingMachine\Domain\Exception\OutOfStockException;
use DomainException;

final class VendingMachine
{
    private MoneyCollection $insertedMoney;
    private MoneyCollection $vault;

    /** @var array<string, array{product: Product, quantity: int}> */
    private array $inventory = [];

    public function __construct(
        private readonly AcceptedCoinsPolicy $acceptedCoinsPolicy
    ) {
        $this->insertedMoney = MoneyCollection::empty();
        $this->vault = MoneyCollection::empty();
    }

    /**
     * @param array<string, array{product: Product, quantity: int}> $inventory
     */
    public function serviceMachine(MoneyCollection $initialChange, array $inventory): void
    {
        $this->vault = $initialChange;
        $this->inventory = $inventory;
    }

    public function insertCoin(Coin $coin): void
    {
        // Defense in depth: The Aggregate Root protects its invariants 
        // by verifying the coin against its acceptance policy before mutating state.
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
            throw new OutOfStockException(sprintf('Product %s is out of stock', $productName));
        }

        $product = $this->inventory[$productName]['product'];
        $insertedTotal = $this->insertedMoney->totalInCents();

        if ($insertedTotal < $product->priceInCents) {
            throw new InsufficientFundsException('Not enough money inserted');
        }

        $changeAmount = $insertedTotal - $product->priceInCents;
        $changeToReturn = $this->calculateChange($changeAmount);

        // Update state: decrease stock, reset inserted money, add inserted to vault, remove change from vault
        $this->inventory[$productName]['quantity']--;
        $this->vault = $this->vaultAdd($this->insertedMoney);
        $this->vault = $this->vaultSubtract($changeToReturn);
        $this->insertedMoney = MoneyCollection::empty();

        return new VendResult($product, $changeToReturn);
    }

    private function calculateChange(int $changeNeededInCents): MoneyCollection
    {
        if ($changeNeededInCents === 0) {
            return MoneyCollection::empty();
        }

        $changeCoins = [];
        $remaining = $changeNeededInCents;

        // Sort vault coins descending to give larger coins first
        $availableCoins = $this->vault->coins();
        usort($availableCoins, fn(Coin $a, Coin $b) => $b->amountInCents <=> $a->amountInCents);

        foreach ($availableCoins as $coin) {
            if ($remaining >= $coin->amountInCents) {
                $changeCoins[] = $coin;
                $remaining -= $coin->amountInCents;
            }
        }

        if ($remaining > 0) {
            throw new DomainException('Machine does not have exact change available');
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
                if ($vaultCoin->amountInCents === $coinToRemove->amountInCents) {
                    unset($currentVaultCoins[$index]);
                    break;
                }
            }
        }

        // Re-index array keys just in case, before spreading
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
}