<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class NoOpenWorkSessionException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No tienes ninguna jornada abierta para terminar.');
    }
}
