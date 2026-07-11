<?php

namespace Tests\Concerns;

use App\Models\Member;
use Illuminate\Support\Str;

trait ActsAsMember
{
    private function actingAsMember(Member $member): static
    {
        $token = (string) Str::uuid();

        $member->devices()->create([
            'device_token' => $token,
            'last_used_at' => now(),
        ]);

        return $this->withCookie('gestiodia_device', $token);
    }
}
