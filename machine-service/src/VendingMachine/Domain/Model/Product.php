<?php

declare(strict_types=1);

namespace App\VendingMachine\Domain\Model;

use App\VendingMachine\Domain\Exception\InvalidProductException;

final readonly class Product
{
    private string $name;
    private int $priceInCents;

    /**
     * @throws InvalidProductException
     */
    public function __construct(string $name, int $priceInCents)
    {
        $sanitizedName = trim($name);

        if ($sanitizedName === '') {
            throw InvalidProductException::forEmptyName();
        }

        if ($priceInCents <= 0) {
            throw InvalidProductException::forInvalidPrice($priceInCents);
        }

        // Assignment happens after validation and sanitization
        $this->name = $sanitizedName;
        $this->priceInCents = $priceInCents;
    }

    /**
     * Creates a Product from a float price safely.
     *
     * @throws InvalidProductException
     */
    public static function fromFloatPrice(string $name, float $price): self
    {
        $priceInCents = (int) round($price * 100);

        return new self($name, $priceInCents);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function priceInCents(): int
    {
        return $this->priceInCents;
    }

    public function price(): float
    {
        return $this->priceInCents / 100;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name() 
            && $this->priceInCents === $other->priceInCents();
    }
}