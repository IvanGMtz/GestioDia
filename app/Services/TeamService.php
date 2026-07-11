<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MemberRole;
use App\Exceptions\TeamCapacityExceededException;
use App\Models\Member;
use App\Models\Team;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamService
{
    /**
     * @return array{team: Team, member: Member, deviceToken: string}
     */
    public function createTeam(string $businessName, string $ownerName): array
    {
        return DB::transaction(function () use ($businessName, $ownerName) {
            $team = Team::create([
                'code' => $this->generateTeamCode(),
                'name' => $businessName,
                'max_members' => 5,
                'settings' => ['timezone' => 'Europe/Madrid'],
            ]);

            $member = $team->members()->create([
                'role' => MemberRole::Employer,
                'name' => $ownerName,
                'active' => true,
            ]);

            return [
                'team' => $team,
                'member' => $member,
                'deviceToken' => $this->createDeviceForMember($member),
            ];
        });
    }

    /**
     * @return array{team: Team, member: Member, deviceToken: string}
     */
    public function joinTeam(string $code, string $memberName): array
    {
        return DB::transaction(function () use ($code, $memberName) {
            $team = Team::withoutGlobalScopes()->where('code', $code)->lockForUpdate()->firstOrFail();

            $activeCount = Member::withoutGlobalScopes()
                ->where('team_id', $team->id)
                ->where('active', true)
                ->count();

            if ($activeCount >= $team->max_members) {
                throw new TeamCapacityExceededException;
            }

            $member = $team->members()->create([
                'role' => MemberRole::Employee,
                'name' => $memberName,
                'active' => true,
            ]);

            return [
                'team' => $team,
                'member' => $member,
                'deviceToken' => $this->createDeviceForMember($member),
            ];
        });
    }

    public function regenerateDeviceForMember(Member $member): string
    {
        return $this->createDeviceForMember($member);
    }

    public function generateTeamCode(): string
    {
        $words = config('team_codes.words');
        $attempts = 0;

        do {
            $word = Arr::random($words);
            $digits = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $code = "{$word}-{$digits}";
            $attempts++;
        } while (Team::withoutGlobalScopes()->where('code', $code)->exists() && $attempts < 10);

        return $code;
    }

    private function createDeviceForMember(Member $member): string
    {
        $token = (string) Str::uuid();

        $member->devices()->create([
            'device_token' => $token,
            'last_used_at' => now(),
        ]);

        return $token;
    }
}
