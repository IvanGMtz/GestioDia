<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use App\Models\MemberDevice;
use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('gestiodia_device');

        if ($token) {
            $device = MemberDevice::where('device_token', $token)->with('member.team')->first();

            if ($device && $device->member?->active) {
                app()->instance(Member::class, $device->member);
                app()->instance(Team::class, $device->member->team);

                if (! $device->last_used_at || $device->last_used_at->lt(now()->subMinutes(5))) {
                    $device->update(['last_used_at' => now()]);
                    $device->member->update(['last_seen_at' => now()]);
                }
            }
        }

        return $next($request);
    }
}
