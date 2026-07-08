<?php

declare(strict_types=1);

namespace App\Enums;

enum MemberRole: string
{
    case Employer = 'EMPLOYER';
    case Employee = 'EMPLOYEE';
}
