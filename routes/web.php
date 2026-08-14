<?php

/* @chisel-teams */
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
/* @end-chisel-teams */
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// @chisel-no-teams-dashboard-route
/* @chisel-teams */
Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function (): void {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function (): void {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});
/* @end-chisel-teams */

require __DIR__.'/settings.php';
