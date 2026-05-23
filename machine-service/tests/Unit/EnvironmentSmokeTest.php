<?php

declare(strict_types=1);

namespace Enterprise\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnvironmentSmokeTest extends TestCase
{
    #[Test]
    public function it_should_verify_phpunit_is_functioning_correctly(): void
    {
        $dateTime = new \DateTimeImmutable('now');

        $this->assertInstanceOf(
            \DateTimeInterface::class,
            $dateTime,
            'The testing environment cannot instantiate core PHP classes.'
        );
    }
}