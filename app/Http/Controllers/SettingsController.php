<?php

namespace App\Http\Controllers;

use App\Http\Requests\LinkEmailRequest;
use App\Models\Member;
use App\Services\MagicLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(): View
    {
        return view('settings.show', ['member' => app(Member::class)]);
    }

    public function linkEmail(LinkEmailRequest $request, MagicLinkService $magicLinkService): RedirectResponse
    {
        $member = app(Member::class);

        $member->update([
            'email' => $request->validated('email'),
            'email_verified_at' => null,
        ]);

        $magicLinkService->issue($member->fresh());

        return back()->with('status', 'Te hemos enviado un enlace a tu correo. Ábrelo para confirmarlo.');
    }
}
