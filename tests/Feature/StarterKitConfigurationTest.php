<?php

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\DevCommands;
use Laravel\Chisel\Script;

test('defines the Laravel community starter kit contract', function () {
    $composer = json_decode(
        file_get_contents(dirname(__DIR__, 2).'/composer.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($composer['name'])->toBe('zacksmash/summit')
        ->and($composer['type'])->toBe('project')
        ->and($composer['extra']['laravel']['installer']['post-create-project'])
        ->toBe(['@php artisan install:features --ansi'])
        ->and($composer['scripts']['post-create-project-cmd'])
        ->toContain('@php artisan passport:keys --no-interaction --ansi')
        ->and(file_get_contents(dirname(__DIR__, 2).'/chisel.php'))
        ->toContain("'tests/Feature/StarterKitConfigurationTest.php'");
});

test('offers all bundled features as default Chisel selections', function () {
    /** @var Script $script */
    $script = require dirname(__DIR__, 2).'/chisel.php';

    $questions = collect($script->questions())->keyBy('name');

    expect($questions->keys()->all())->toBe([
        'auth_features',
        'application_features',
        'development_features',
    ])->and($questions['auth_features']->default)
        ->toBe(array_keys($questions['auth_features']->options))
        ->and($questions['application_features']->default)
        ->toBe(array_keys($questions['application_features']->options))
        ->and($questions['development_features']->default)
        ->toBe(array_keys($questions['development_features']->options));
});

test('keeps the portable and machine-specific setup workflows separate', function () {
    $composer = json_decode(
        file_get_contents(dirname(__DIR__, 2).'/composer.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $setup = implode("\n", $composer['scripts']['setup']);
    $octaneSetup = implode("\n", $composer['scripts']['setup:octane']);
    $toolsSetup = implode("\n", $composer['scripts']['setup:tools']);

    expect($setup)
        ->toContain('composer install')
        ->toContain('artisan passport:keys')
        ->toContain('npm install')
        ->toContain('npm run build')
        ->not->toContain('git init')
        ->not->toContain('migrate:fresh')
        ->not->toContain('herd ')
        ->not->toContain('playwright')
        ->not->toContain('whisky')
        ->not->toContain('ide-helper')
        ->and($octaneSetup)
        ->toContain('octane:install --server=frankenphp')
        ->and($toolsSetup)
        ->toStartWith('git init -q')
        ->toContain('@setup:octane')
        ->toContain('herd secure')
        ->toContain('herd proxy')
        ->toContain('playwright install')
        ->toContain('whisky install')
        ->toContain('ide-helper:generate')
        ->toContain('npm run format')
        ->toEndWith('git diff --cached --quiet || git commit --no-verify -m "Initial commit"');
});

/* @chisel-octane */
test('falls back to the Laravel development server without an Octane runtime', function () {
    config()->set('octane.server', 'unavailable');

    (new AppServiceProvider(app()))->boot();

    $server = collect(DevCommands::commands())->firstWhere('name', 'server');

    expect($server['command'])->toBe('php artisan serve');
});
/* @end-chisel-octane */
