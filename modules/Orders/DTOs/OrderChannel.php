<?php

declare(strict_types=1);

namespace Modules\Orders\DTOs;

/**
 * Where an order was placed. Lives in DTOs because it is part of the
 * module's public data (other modules may read it).
 */
enum OrderChannel: string
{
    case Web = 'web';
    case Pos = 'pos';
    case WhatsApp = 'whatsapp';
    case Agent = 'agent';
}
