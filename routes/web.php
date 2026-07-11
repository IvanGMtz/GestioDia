<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::prefix('equipo')->name('team.')->group(function () {
    Route::get('crear', [TeamController::class, 'createShow'])->name('create.show');
    Route::post('crear', [TeamController::class, 'createStore'])->name('create.store')->middleware('throttle:team-create');
    Route::get('unirse', [TeamController::class, 'joinShow'])->name('join.show');
    Route::post('unirse', [TeamController::class, 'joinStore'])->name('join.store')->middleware('throttle:team-join');
});

Route::middleware('member')->group(function () {
    Route::get('inicio', [HomeController::class, 'show'])->name('home.authenticated');
});
