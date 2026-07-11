<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberResolved
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->bound(Member::class)) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
