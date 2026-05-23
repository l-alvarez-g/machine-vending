<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

final readonly class VendResult
{
    public function __construct(
        public Product $product,
        public MoneyCollection $change
    ) {
    }
}