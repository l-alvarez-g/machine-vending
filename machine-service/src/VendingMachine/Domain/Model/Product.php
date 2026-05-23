<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InvalidProductException;

final readonly class Product
{
    public int $priceInCents;

    public function __construct(
        public string $name,
        float $price
    ) {
        if (trim($name) === '') {
            throw new InvalidProductException('Product name cannot be empty');
        }

        // Safe conversion avoiding float precision loss
        $priceInCents = (int) round($price * 100);

        if ($priceInCents <= 0) {
            throw new InvalidProductException('Product price must be greater than zero');
        }

        $this->priceInCents = $priceInCents;
    }

    public function price(): float
    {
        return $this->priceInCents / 100;
    }
}