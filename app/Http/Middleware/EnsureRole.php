<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $member = app(Member::class);

        abort_unless($member->role->value === $role, 403);

        return $next($request);
    }
}
