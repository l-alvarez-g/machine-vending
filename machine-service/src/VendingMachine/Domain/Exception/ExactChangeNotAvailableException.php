<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Exception;

use DomainException;

final class ExactChangeNotAvailableException extends DomainException
{
}