<?php

use App\Models\User;
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

test('keeps the sidebar content sticky within the viewport', function () {
    expect(file_get_contents(dirname(__DIR__, 2).'/resources/js/layouts/app/AppSidebarLayout.vue'))
        ->toContain('class="min-w-0 overflow-x-clip"')
        ->not->toContain('class="overflow-x-hidden"');
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
        ->toContain('artisan passport:keys --no-interaction --force')
        ->toContain('npm install')
        ->toContain('npx playwright install')
        ->toContain('npm run build')
        ->not->toContain('git init')
        ->not->toContain('migrate:fresh')
        ->not->toContain('herd ')
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
        ->toEndWith('git rev-parse -q --verify HEAD >/dev/null 2>&1 || (git add --all && git commit --no-verify -m "Initial commit")');
});

test('seeds the database idempotently', function () {
    $this->seed();
    $this->seed();

    expect(User::query()->where('email', 'test@example.com')->count())->toBe(1);
});

test('keeps personal tunneling tooling out of the template', function () {
    $root = dirname(__DIR__, 2);

    $composer = json_decode(file_get_contents($root.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
    $package = json_decode(file_get_contents($root.'/package.json'), true, 512, JSON_THROW_ON_ERROR);

    expect(implode("\n", $composer['scripts']['serve']))
        ->not->toContain('ngrok')
        ->not->toContain('dotenv-cli')
        ->and(file_get_contents($root.'/.env.example'))->not->toContain('NGROK_URL')
        ->and($package['devDependencies'])->toHaveKey('@laravel/multiplex')
        ->and($package['optionalDependencies'])->not->toHaveKey('@laravel/multiplex');
});

test('installs Playwright browsers in the installer flow when browser testing is kept', function () {
    expect(file_get_contents(dirname(__DIR__, 2).'/chisel.php'))
        ->toContain("chiselRun(['npx', 'playwright', 'install'], 'Install Playwright Browsers')");
});

test('runs feature selection after the other post-update scripts', function () {
    $composer = json_decode(
        file_get_contents(dirname(__DIR__, 2).'/composer.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($composer['scripts']['post-update-cmd'])->toBe([
        '@php artisan vendor:publish --tag=laravel-assets --ansi --force',
        '@php artisan boost:update --env=local --ansi',
        '@php artisan install:features --ansi',
    ]);
});

test('keeps the views setting when the passkeys section is removed', function () {
    $config = preg_replace(
        '/\/\* @chisel-passkeys \*\/.*?\/\* @end-chisel-passkeys \*\//s',
        '',
        file_get_contents(dirname(__DIR__, 2).'/config/fortify.php'),
    );

    expect($config)->toContain("'views' => true,");
});

test('drops laravel/mcp whenever the MCP scaffolding is deselected', function () {
    expect(file_get_contents(dirname(__DIR__, 2).'/chisel.php'))
        ->toContain("if (! chiselSelected(\$answers, 'application_features', 'mcp')) {");
});

test('removes the Chisel toolkit from generated projects', function () {
    expect(file_get_contents(dirname(__DIR__, 2).'/chisel.php'))
        ->toContain("'laravel/chisel',");
});

test('rewrites the serve script when Octane is deselected', function () {
    expect(file_get_contents(dirname(__DIR__, 2).'/chisel.php'))
        ->toContain("->replace('php artisan octane:start --watch', 'php artisan serve')");
});

test('rebuilds the database schema after removing deselected migrations', function () {
    expect(file_get_contents(dirname(__DIR__, 2).'/chisel.php'))
        ->toContain("'migrate:fresh'");
});

test('skips feature selection when the session is not interactive', function () {
    $this->artisan('install:features', ['--no-interaction' => true])
        ->assertSuccessful();

    expect(file_exists(base_path('chisel.php')))->toBeTrue()
        ->and(file_exists(base_path('chisel-paths.php')))->toBeTrue();
});

/* @chisel-octane */
test('falls back to the Laravel development server without an Octane runtime', function () {
    config()->set('octane.server', 'unavailable');

    $provider = new AppServiceProvider(app());

    Closure::bind(fn () => $this->registerDevCommands(), $provider, AppServiceProvider::class)();

    $server = collect(DevCommands::commands())->firstWhere('name', 'server');

    expect($server['command'])->toBe('php artisan serve');
});
/* @end-chisel-octane */
