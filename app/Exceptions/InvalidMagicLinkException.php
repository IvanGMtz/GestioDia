<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class InvalidMagicLinkException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este enlace no es válido, ya se usó o ha caducado.');
    }
}
