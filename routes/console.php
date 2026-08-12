<?php

declare(strict_types=1);

/* @chisel-teams */
use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function (): void {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');
/* @end-chisel-teams */
