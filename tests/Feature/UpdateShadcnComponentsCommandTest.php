<?php

use App\Console\Commands\UpdateShadcnComponentsCommand;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * @return list<string>
 */
function installedShadcnComponents(): array
{
    return collect(File::directories(resource_path('js/components/ui')))
        ->map(fn (string $directory): string => basename($directory))
        ->sort()
        ->values()
        ->all();
}

test('reinstalls every installed component and then applies the starter kit patch', function () {
    Process::fake();

    $this->artisan('shadcn:update')->assertSuccessful();

    $components = installedShadcnComponents();

    expect($components)->toContain('sidebar', 'sonner');

    Process::assertRanTimes(fn (PendingProcess $process): bool => $process->command === [
        'npx', 'shadcn-vue', 'add', ...$components, '--overwrite', '--yes',
    ], 1);
    Process::assertRan(fn (PendingProcess $process): bool => $process->command === [
        'git', 'apply', UpdateShadcnComponentsCommand::STARTER_KIT_PATCH,
    ]);
});

test('does not apply the starter kit patch when the reinstall fails', function () {
    Process::fake([
        '*shadcn-vue*' => Process::result(exitCode: 1),
    ]);

    $this->artisan('shadcn:update')
        ->expectsOutputToContain('The starter kit patch was not applied.')
        ->assertFailed();

    Process::assertDidntRun(fn (PendingProcess $process): bool => $process->command[0] === 'git');
});

test('fails when the starter kit patch no longer applies', function () {
    Process::fake([
        '*shadcn-vue*' => Process::result(),
        '*git*' => Process::result(errorOutput: 'error: patch failed', exitCode: 1),
    ]);

    $this->artisan('shadcn:update')
        ->expectsOutputToContain('The starter kit patch no longer applies')
        ->assertFailed();
});

test('the starter kit patch matches the committed components', function () {
    $result = Process::path(base_path())->run([
        'git', 'apply', '--reverse', '--check', UpdateShadcnComponentsCommand::STARTER_KIT_PATCH,
    ]);

    expect($result->successful())->toBeTrue($result->errorOutput());
});
