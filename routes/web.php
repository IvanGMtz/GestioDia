<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\OneOffTaskController;
use App\Http\Controllers\RecurringTaskController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WorkSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::prefix('equipo')->name('team.')->group(function () {
    Route::get('crear', [TeamController::class, 'createShow'])->name('create.show');
    Route::post('crear', [TeamController::class, 'createStore'])->name('create.store')->middleware('throttle:team-create');
    Route::get('unirse', [TeamController::class, 'joinShow'])->name('join.show');
    Route::post('unirse', [TeamController::class, 'joinStore'])->name('join.store')->middleware('throttle:team-join');
});

Route::middleware('member')->group(function () {
    Route::get('tareas/hoy', [TaskController::class, 'today'])
        ->middleware('tasks.ensure-generated')
        ->name('tasks.today');

    Route::post('tareas/{task}/completar', [TaskController::class, 'complete'])->name('tasks.complete');

    Route::post('fichaje/entrada', [WorkSessionController::class, 'clockIn'])->name('work-sessions.clock-in');
    Route::post('fichaje/salida', [WorkSessionController::class, 'clockOut'])->name('work-sessions.clock-out');
    Route::get('mi-semana', [WorkSessionController::class, 'mine'])->name('work-sessions.mine');

    Route::middleware('role:EMPLOYER')->group(function () {
        Route::get('tareas', [RecurringTaskController::class, 'index'])->name('tasks.index');
        Route::get('tareas/crear', [RecurringTaskController::class, 'create'])->name('tasks.recurring.create');
        Route::post('tareas', [RecurringTaskController::class, 'store'])->name('tasks.recurring.store');
        Route::get('tareas/{recurringTask}/editar', [RecurringTaskController::class, 'edit'])->name('tasks.recurring.edit');
        Route::put('tareas/{recurringTask}', [RecurringTaskController::class, 'update'])->name('tasks.recurring.update');
        Route::delete('tareas/{recurringTask}', [RecurringTaskController::class, 'destroy'])->name('tasks.recurring.destroy');
        Route::post('tareas/puntual', [OneOffTaskController::class, 'store'])->name('tasks.oneoff.store');
        Route::get('tareas/puntual/{task}/editar', [OneOffTaskController::class, 'edit'])->name('tasks.oneoff.edit');
        Route::put('tareas/puntual/{task}', [OneOffTaskController::class, 'update'])->name('tasks.oneoff.update');
        Route::delete('tareas/puntual/{task}', [OneOffTaskController::class, 'destroy'])->name('tasks.oneoff.destroy');

        Route::get('equipo/jornadas', [WorkSessionController::class, 'weekly'])->name('work-sessions.weekly');
        Route::get('equipo/jornadas/export', [WorkSessionController::class, 'export'])->name('work-sessions.export');
        Route::put('equipo/jornadas/{workSession}', [WorkSessionController::class, 'update'])->name('work-sessions.update');
    });
});
