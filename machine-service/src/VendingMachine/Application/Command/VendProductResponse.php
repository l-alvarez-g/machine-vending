<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

final readonly class VendProductResponse
{
    /**
     * @param string $productName
     * @param float $productPrice
     * @param float $changeReturned
     */
    public function __construct(
        public string $productName,
        public float $productPrice,
        public float $changeReturned
    ) {
    }
}