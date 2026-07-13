<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidMagicLinkException;
use App\Http\Requests\RequestMagicLinkRequest;
use App\Models\Member;
use App\Services\MagicLinkService;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MagicLinkController extends Controller
{
    public function showRequestForm(): View
    {
        return view('magic-link.request');
    }

    public function sendLoginLink(RequestMagicLinkRequest $request, MagicLinkService $magicLinkService): RedirectResponse
    {
        $member = Member::where('email', $request->validated('email'))
            ->whereNotNull('email_verified_at')
            ->first();

        if ($member) {
            $magicLinkService->issue($member);
        }

        // Mensaje siempre igual, exista o no la cuenta: evita revelar qué
        // correos están registrados (enumeración de usuarios).
        return back()->with('status', 'Si ese correo está vinculado a una cuenta, te hemos enviado un enlace de acceso.');
    }

    public function consume(
        string $token,
        Request $request,
        MagicLinkService $magicLinkService,
        TeamService $teamService
    ): RedirectResponse {
        try {
            $member = $magicLinkService->consume($token);
        } catch (InvalidMagicLinkException $e) {
            return redirect()->route('home')->with('magic_link_error', $e->getMessage());
        }

        $deviceToken = $teamService->regenerateDeviceForMember($member);

        $this->rememberDevice($request, $deviceToken);

        return redirect()->route('tasks.today');
    }
}
