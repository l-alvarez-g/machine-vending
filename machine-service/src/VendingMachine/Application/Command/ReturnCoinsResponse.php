<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

final readonly class ReturnCoinsResponse
{
    /**
     * @param float $totalReturned The total amount returned formatted as float for external consumers.
     */
    public function __construct(
        public float $totalReturned
    ) {
    }
}