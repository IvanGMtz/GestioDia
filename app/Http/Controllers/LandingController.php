<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (app()->bound(Member::class)) {
            return redirect()->route('home.authenticated');
        }

        return view('welcome');
    }
}
