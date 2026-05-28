<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Parser;

use App\VendingMachine\Application\Query\MachineStateDTO;
use InvalidArgumentException;

final readonly class ServicePayloadParser
{
    /** @var array<int, float> */
    private array $validCoinsFloat;

    /**
     * @param array<int, string> $validCoins Array of raw config strings (e.g. ['0.05', '1.00'])
     * @param array<string, float> $productPrices
     */
    public function __construct(
        private array $validCoins,
        private array $productPrices
    ) {
        // Pre-compute float values once for performance and strict type checking
        $this->validCoinsFloat = array_map('floatval', $this->validCoins);
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
            // If it starts with a letter, assume it's an inventory payload
            if (preg_match('/^[A-Z]/i', $payload)) {
                $inventoryPayload = $payload;
            } else {
                $coinsPayload = $payload;
            }
        }

        return [
            'coins'     => $this->parseCoins($coinsPayload, $currentState),
            'inventory' => $this->parseInventory($inventoryPayload, $currentState)
        ];
    }

    /**
     * @return array<int, float>
     */
    private function parseCoins(string $payload, MachineStateDTO $currentState): array
    {
        $coinsToAdd = [];

        // If no coin payload is provided, preserve current vault state
        if ($payload === '') {
            foreach ($currentState->vaultCoins as $valStr => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $coinsToAdd[] = (float) $valStr;
                }
            }
            return $coinsToAdd;
        }

        $coinEntries = explode(';', $payload);

        foreach ($coinEntries as $entry) {
            $entryParts = explode(':', $entry);
            if (count($entryParts) === 2 && is_numeric($entryParts[0]) && is_numeric($entryParts[1])) {
                $coinValue = (float) $entryParts[0];
                $qty = (int) $entryParts[1];

                // Strict check against the pre-computed valid float values.
                // This safely allows a user to input "1" and match the config "1.00".
                if (!in_array($coinValue, $this->validCoinsFloat, true)) {
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
        // If no inventory payload is provided, preserve current inventory state
        if ($payload === '') {
            return $currentState->inventory;
        }

        $inventoryData = [];
        $itemEntries = explode(';', $payload);

        foreach ($itemEntries as $entry) {
            $entryParts = explode(':', $entry);
            if (count($entryParts) === 2 && is_numeric($entryParts[1])) {
                $itemName = strtoupper(trim($entryParts[0]));
                $qty = (int) $entryParts[1];

                if (!isset($this->productPrices[$itemName])) {
                    throw new InvalidArgumentException(sprintf('Unknown product: %s', $itemName));
                }

                $inventoryData[$itemName] = [
                    'price'    => $this->productPrices[$itemName],
                    'quantity' => $qty
                ];
            }
        }

        return $inventoryData;
    }
}