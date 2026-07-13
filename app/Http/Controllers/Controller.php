<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    private const DEVICE_COOKIE_MINUTES = 60 * 24 * 400;

    protected function rememberDevice(Request $request, string $deviceToken): void
    {
        cookie()->queue(cookie(
            'gestiodia_device',
            $deviceToken,
            self::DEVICE_COOKIE_MINUTES,
            '/',
            null,
            $request->secure(),
            true,
            false,
            'lax',
        ));
    }
}
