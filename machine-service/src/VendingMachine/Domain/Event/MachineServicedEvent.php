<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final readonly class MachineServicedEvent implements DomainEvent
{
    public function __construct(
        public string $machineId,
        public \DateTimeImmutable $occurredOn = new \DateTimeImmutable()
    ) {
    }
}