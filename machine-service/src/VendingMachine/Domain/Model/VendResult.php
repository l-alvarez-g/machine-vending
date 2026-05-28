<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

final readonly class VendResult
{
    public function __construct(
        private Product $product,
        private MoneyCollection $change
    ) {
    }

    public function product(): Product
    {
        return $this->product;
    }

    public function change(): MoneyCollection
    {
        return $this->change;
    }
}