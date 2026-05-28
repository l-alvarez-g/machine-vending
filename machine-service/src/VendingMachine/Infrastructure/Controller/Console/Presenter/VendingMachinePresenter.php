<?php

declare(strict_types=1);

namespace App\VendingMachine\Infrastructure\Controller\Console\Presenter;

use App\VendingMachine\Application\Command\ReturnCoinsResponse;
use App\VendingMachine\Application\Query\MachineStateDTO;
use Symfony\Component\Console\Output\OutputInterface;

final class VendingMachinePresenter
{
    public function displayStatus(OutputInterface $output, string $title, MachineStateDTO $state): void
    {
        $output->writeln(sprintf("\n<info>=== %s ===</info>", $title));

        $output->writeln('<comment>Vault (Available Change):</comment>');
        if ($state->vaultCoins === []) {
            $output->writeln('  [Empty]');
        } else {
            foreach ($state->vaultCoins as $val => $count) {
                $output->writeln(sprintf('  $%s : %d units', $val, $count));
            }
        }

        $output->writeln("\n<comment>Inventory:</comment>");
        if ($state->inventory === []) {
            $output->writeln('  [Empty]');
        } else {
            foreach ($state->inventory as $name => $data) {
                $output->writeln(sprintf('  %s : %d units ($%.2f)', $name, $data['quantity'], $data['price']));
            }
        }
        $output->writeln("<info>=========================</info>\n");
    }


    public function formatReturnedCoins(ReturnCoinsResponse $response): string
    {
        if ($response->returnedCoins === []) {
            return '';
        }

        $formatted = array_map(
            static fn (float $coin) => $coin == 1.0 ? '1' : number_format($coin, 2, '.', ''),
            $response->returnedCoins
        );

        return implode(', ', $formatted);
    }
}