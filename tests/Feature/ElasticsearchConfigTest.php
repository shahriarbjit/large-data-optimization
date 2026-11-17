<?php

declare(strict_types=1);

it('reads the Elasticsearch host from config', function (): void {
    expect(config('services.elasticsearch.host'))
        ->toBeString()
        ->toStartWith('http');
});
