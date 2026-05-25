<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Parser;

use App\VendingMachine\Application\Query\MachineStateDTO;
use InvalidArgumentException;

final readonly class ServicePayloadParser
{
    /**
     * @param array<int, string|float> $validCoins
     * @param array<string, float> $productPrices
     */
    public function __construct(
        private array $validCoins,
        private array $productPrices
    ) {
    }

    /**
     * @return array{coins: array<int, float>, inventory: array<string, array{price: float, quantity: int}>}
     * @throws InvalidArgumentException
     */
    public function parse(string $payload, MachineStateDTO $currentState): array
    {
        $coinsPayload = '';
        $inventoryPayload = '';

        if (str_contains($payload, '|')) {
            [$coinsPayload, $inventoryPayload] = explode('|', $payload, 2);
        } else {
            if (preg_match('/^[A-Z]/', $payload)) {
                $inventoryPayload = $payload;
            } else {
                $coinsPayload = $payload;
            }
        }

        return [
            'coins' => $this->parseCoins($coinsPayload, $currentState),
            'inventory' => $this->parseInventory($inventoryPayload, $currentState)
        ];
    }

    /**
     * @return array<int, float>
     */
    private function parseCoins(string $payload, MachineStateDTO $currentState): array
    {
        $coinsToAdd = [];

        if ($payload === '') {
            foreach ($currentState->vaultCoins as $valStr => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $coinsToAdd[] = (float) $valStr;
                }
            }
            return $coinsToAdd;
        }

        $validCoinsFloat = array_map('floatval', $this->validCoins);
        $coinEntries = explode(';', $payload);

        foreach ($coinEntries as $entry) {
            $entryParts = explode(':', $entry);
            if (count($entryParts) === 2 && is_numeric($entryParts[0]) && is_numeric($entryParts[1])) {
                $coinValue = (float) $entryParts[0];
                $qty = (int) $entryParts[1];

                if (!in_array($coinValue, $validCoinsFloat, true)) {
                    throw new InvalidArgumentException(sprintf('Invalid coin detected: %s', $entryParts[0]));
                }

                for ($i = 0; $i < $qty; $i++) {
                    $coinsToAdd[] = $coinValue;
                }
            }
        }

        return $coinsToAdd;
    }

    /**
     * @return array<string, array{price: float, quantity: int}>
     */
    private function parseInventory(string $payload, MachineStateDTO $currentState): array
    {
        if ($payload === '') {
            return $currentState->inventory;
        }

        $inventoryData = [];
        $itemEntries = explode(';', $payload);

        foreach ($itemEntries as $entry) {
            $entryParts = explode(':', $entry);
            if (count($entryParts) === 2 && is_numeric($entryParts[1])) {
                $itemName = $entryParts[0]; 
                $qty = (int) $entryParts[1];

                if (!isset($this->productPrices[$itemName])) {
                    throw new InvalidArgumentException(sprintf('Unknown product: %s', $itemName));
                }

                $inventoryData[$itemName] = [
                    'price' => $this->productPrices[$itemName],
                    'quantity' => $qty
                ];
            }
        }

        return $inventoryData;
    }
}