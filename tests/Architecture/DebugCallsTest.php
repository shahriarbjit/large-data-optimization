<?php

declare(strict_types=1);

// Placeholder so the Architecture suite is not empty. Expanded in step 6.
arch('no debug helpers in application code')
    ->expect(['dd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsed();
