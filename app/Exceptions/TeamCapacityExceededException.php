<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class TeamCapacityExceededException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('El equipo ya ha alcanzado su número máximo de miembros.');
    }
}
