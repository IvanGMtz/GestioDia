<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Team;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function show(): View
    {
        return view('home', [
            'team' => app(Team::class),
            'member' => app(Member::class),
        ]);
    }
}
