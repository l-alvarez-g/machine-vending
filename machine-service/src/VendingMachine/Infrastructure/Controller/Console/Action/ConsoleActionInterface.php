<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Action;

use Symfony\Component\Console\Output\OutputInterface;

interface ConsoleActionInterface
{
    /**
     * Evaluates if this strategy should handle the given token.
     */
    public function supports(string $tokenUpper): bool;

    /**
     * Executes the action.
     * Returns a string if there is a response to accumulate (e.g., returned coins or product name), or null.
     */
    public function execute(string $tokenUpper, OutputInterface $output): ?string;
}