<?php

declare(strict_types=1);

namespace Modules\Shared\Exceptions;

/**
 * The input is well-formed but breaks a business rule
 * (e.g. not enough stock). Always pass a specific code:
 * new DomainRuleException('Only 2 left in stock.', 'insufficient_stock').
 */
final class DomainRuleException extends ApiException
{
    public function status(): int
    {
        return 422;
    }

    public function title(): string
    {
        return 'Business Rule Violated';
    }

    protected function defaultErrorCode(): string
    {
        return 'domain_rule_violated';
    }

    protected function defaultDetail(): string
    {
        return 'The request breaks a business rule.';
    }
}
