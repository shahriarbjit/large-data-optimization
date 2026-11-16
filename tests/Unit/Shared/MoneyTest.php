<?php

declare(strict_types=1);

use Modules\Shared\Support\Money;

it('normalises amounts to two decimals', function (string $input, string $expected): void {
    expect(Money::of($input)->amount)->toBe($expected);
})->with([
    ['1250', '1250.00'],
    ['1250.5', '1250.50'],
    ['1250.55', '1250.55'],
    ['0', '0.00'],
    ['-10.1', '-10.10'],
]);

it('defaults to BDT', function (): void {
    expect(Money::of('1')->currency)->toBe('BDT');
});

it('rejects invalid amounts', function (string $input): void {
    Money::of($input);
})->with(['1,250.00', '12.345', 'abc', '', '1e3', '.5'])->throws(InvalidArgumentException::class);

it('rejects invalid currencies', function (): void {
    Money::of('1.00', 'bdt');
})->throws(InvalidArgumentException::class);

it('serialises to JSON as strings, never floats', function (): void {
    expect(json_encode(Money::of('1250', 'BDT')))->toBe('{"amount":"1250.00","currency":"BDT"}');
});

it('compares by value', function (): void {
    expect(Money::of('5')->equals(Money::of('5.00')))->toBeTrue()
        ->and(Money::of('5')->equals(Money::of('5', 'USD')))->toBeFalse();
});
