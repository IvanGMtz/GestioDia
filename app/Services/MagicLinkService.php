<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidMagicLinkException;
use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\Member;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MagicLinkService
{
    private const EXPIRY_MINUTES = 15;

    public function issue(Member $member): void
    {
        $plainToken = $this->createToken($member);

        Mail::to($member->email)->send(new MagicLinkMail($plainToken));
    }

    /**
     * Genera un enlace de recuperación sin depender de email (para cuando el
     * EMPLOYER necesita restaurar el acceso de alguien que no vinculó correo).
     * Devuelve la URL en claro para que el EMPLOYER la comparta manualmente.
     */
    public function issueRecoveryLink(Member $member): string
    {
        $plainToken = $this->createToken($member);

        return route('magic-link.consume', ['token' => $plainToken]);
    }

    public function consume(string $plainToken): Member
    {
        $hashed = hash('sha256', $plainToken);

        $magicLink = MagicLink::where('token', $hashed)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $magicLink) {
            throw new InvalidMagicLinkException;
        }

        $magicLink->update(['used_at' => now()]);

        $member = $magicLink->member;

        if (! $member->email_verified_at && $member->email) {
            $member->update(['email_verified_at' => now()]);
        }

        return $member;
    }

    private function createToken(Member $member): string
    {
        $plainToken = Str::random(64);

        $member->magicLinks()->create([
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        return $plainToken;
    }
}
