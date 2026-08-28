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

test('ignores generated and tool-owned paths in the Vite watcher', function () {
    $viteConfig = file_get_contents(dirname(__DIR__, 2).'/vite.config.ts');

    expect($viteConfig)
        ->toContain('server: {')
        ->toContain("'**/.agents/**'")
        ->toContain("'**/.ai/**'")
        ->toContain("'**/.claude/**'")
        ->toContain("'**/.codex/**'")
        ->toContain("'**/.cursor/**'")
        ->toContain("'**/.junie/**'")
        ->toContain("'**/vendor/**'");
});

test('tracks current Laravel skeleton housekeeping', function () {
    $root = dirname(__DIR__, 2);

    expect(file_get_contents($root.'/config/logging.php'))
        ->toContain('Available drivers: "single", "daily", "monthly", "slack", "syslog",')
        ->and(file_get_contents($root.'/storage/framework/.gitignore'))
        ->toContain('lsp-*.php');
});

test('uses Vite Plus for frontend development and code quality', function () {
    $root = dirname(__DIR__, 2);
    $package = json_decode(file_get_contents($root.'/package.json'), true, 512, JSON_THROW_ON_ERROR);
    $composer = json_decode(file_get_contents($root.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
    $viteConfig = file_get_contents($root.'/vite.config.ts');
    $chisel = file_get_contents($root.'/chisel.php');

    expect($package['scripts'])->toBe([
        'build' => 'vp build',
        'build:ssr' => 'vp build && vp build --ssr',
        'dev' => 'vp dev',
        'check' => 'vp check',
        'check:fix' => 'vp check --fix',
        'types:check' => 'vue-tsc --noEmit',
    ])->and($package['devDependencies'])
        ->toHaveKey('vite-plus', '0.3.0')
        ->not->toHaveKey('oxfmt')
        ->not->toHaveKey('oxlint')
        ->not->toHaveKey('oxlint-tsgolint')
        ->and($composer['scripts']['ci:check'])->toBe([
            'Composer\\Config::disableProcessTimeout',
            'npm run check',
            'npm run types:check',
            '@test',
        ])->and(implode("\n", $composer['scripts']['serve']))
        ->toContain("'vite@green,vp build'")
        ->and($viteConfig)
        ->toContain("import { defineConfig, lazyPlugins } from 'vite-plus';")
        ->toContain('plugins: lazyPlugins(() => [')
        ->toContain('denyWarnings: true')
        ->toContain('typeAware: true')
        ->toContain("entryPoint: 'resources/css/app.css'")
        ->toContain("'resources/js/components/ai-elements/*'")
        ->and($chisel)
        ->toContain("\$c->npm()->run('check:fix');")
        ->not->toContain("\$c->npm()->run('lint');")
        ->not->toContain("\$c->npm()->run('format');")
        ->and(file_get_contents($root.'/resources/js/app.ts'))
        ->toContain('void createInertiaApp({')
        ->and(file_exists($root.'/.oxfmtrc.json'))->toBeFalse()
        ->and(file_exists($root.'/.oxlintrc.json'))->toBeFalse();
});

test('runs the consolidated Laravel CI workflow across supported PHP versions', function () {
    $root = dirname(__DIR__, 2);
    $workflow = file_get_contents($root.'/.github/workflows/tests.yml');

    expect(file_exists($root.'/.github/workflows/lint.yml'))->toBeFalse()
        ->and($workflow)
        ->toContain('actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1')
        ->toContain('shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240 # v2')
        ->toContain('actions/setup-node@820762786026740c76f36085b0efc47a31fe5020 # v7.0.0')
        ->toContain("php-version: ['8.4', '8.5']")
        ->toContain('coverage: none')
        ->toContain('run: composer setup')
        ->toContain('run: npx playwright install --with-deps')
        ->toContain('run: composer ci:check')
        ->not->toContain('run: php artisan test')
        ->not->toContain('contents: write');
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
        ->toContain('npm run check:fix')
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
