<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Placeholder money value object. Amounts are decimal strings with exactly
 * two decimals ("1250.00"); floats are never used.
 *
 * In JSON:      "total": "1250.00", "currency": "BDT"
 * In database:  DECIMAL(12,2) plus a CHAR(3) currency column (when tables exist)
 *
 * Arithmetic is added when the first feature needs it (bcmath, see docs/growth-path.md).
 */
final readonly class Money implements JsonSerializable
{
    private function __construct(
        public string $amount,
        public string $currency,
    ) {}

    /**
     * Accepts "1250", "1250.5" or "1250.50" (and negatives). Rejects floats,
     * thousands separators and more than two decimals.
     */
    public static function of(string $amount, string $currency = 'BDT'): self
    {
        if (preg_match('/^-?\d+(\.\d{1,2})?$/', $amount) !== 1) {
            throw new InvalidArgumentException("Invalid money amount [{$amount}]. Use a decimal string with up to two decimals.");
        }

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new InvalidArgumentException("Invalid currency [{$currency}]. Use an ISO 4217 code such as BDT.");
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return new self($whole.'.'.str_pad($fraction, 2, '0'), $currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    /**
     * @return array{amount: string, currency: string}
     */
    public function jsonSerialize(): array
    {
        return ['amount' => $this->amount, 'currency' => $this->currency];
    }
}
