<?php

declare(strict_types=1);

namespace App\VendingMachine\Application\Command;

final readonly class InsertCoinCommand
{
    public function __construct(public float $amount)
    {
    }
}