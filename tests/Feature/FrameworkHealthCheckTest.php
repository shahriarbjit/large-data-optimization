<?php

declare(strict_types=1);

it('serves the framework health check at /up', function (): void {
    $this->get('/up')->assertOk();
});
