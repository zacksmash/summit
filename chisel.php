<?php

require getenv('LARAVEL_INSTALLER_AUTOLOADER') ?: __DIR__.'/vendor/autoload.php';

use Laravel\Chisel\Chisel;
use Laravel\Chisel\Question;
use Laravel\Prompts\Support\Logger;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\task;

function chiselRun(array $command, string $label): void
{
    $process = task(
        label: $label,
        keepSummary: true,
        callback: function (Logger $logger) use ($command) {
            $process = new Process($command, __DIR__);
            $process->run(function ($type, $line) use ($logger) {
                $logger->line($line);
            });

            if ($process->isSuccessful()) {
                $logger->success(implode(' ', $command));

                return $process;
            }

            $logger->error(implode(' ', $command));
            $logger->error('Error output: '.trim($process->getErrorOutput()));
            $logger->error('Chisel: Your project may be in a partially-modified state — review the output above before continuing.');

            return $process;
        },
    );

    if (! $process->isSuccessful()) {
        exit($process->getExitCode());
    }
}

function chiselSkipsNode(): bool
{
    return filter_var(
        $_ENV['LARAVEL_INSTALLER_NO_NODE']
            ?? $_SERVER['LARAVEL_INSTALLER_NO_NODE']
            ?? getenv('LARAVEL_INSTALLER_NO_NODE'),
        FILTER_VALIDATE_BOOL,
    );
}

function chiselRemoveNpmPackages(Chisel $c, string ...$packages): void
{
    if (! chiselSkipsNode()) {
        $c->npm()->remove(...$packages);

        return;
    }

    foreach ($packages as $package) {
        $c->file('package.json')->removeLinesContaining('"'.$package.'":');
    }
}

/**
 * @param  list<string>  $packages
 */
function chiselRemoveComposerPackages(array $packages, bool $dev = false): void
{
    if ($packages === []) {
        return;
    }

    chiselRun([
        'composer',
        'remove',
        ...($dev ? ['--dev'] : []),
        '--no-interaction',
        '--no-scripts',
        '--no-audit',
        '--minimal-changes',
        ...$packages,
    ], $dev ? 'Remove Composer Development Packages' : 'Remove Composer Packages');
}

function chiselDeleteDirectory(string $directory): void
{
    $path = __DIR__.'/'.$directory;

    if (! is_dir($path)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($files as $file) {
        ($file->isDir() && ! $file->isLink())
            ? rmdir($file->getPathname())
            : unlink($file->getPathname());
    }

    rmdir($path);
}

/**
 * @param  array<string, mixed>  $answers
 */
function chiselSelected(array $answers, string $question, string $option): bool
{
    return in_array($option, (array) ($answers[$question] ?? []), true);
}

/**
 * Framework-specific filenames are supplied by the sibling chisel-paths.php
 * that ships with each Inertia kit (React/Svelte/Vue). After build both files
 * land in the project root.
 *
 * @var array{
 *     login: string,
 *     register: string,
 *     welcome: string,
 *     profile: string,
 *     security: string,
 *     verify_email: string,
 *     two_factor_challenge: string,
 *     confirm_password: string,
 *     auth_types: string,
 *     two_factor_files: list<string>,
 *     two_factor_otp_package: ?string,
 *     passkey_files: list<string>,
 *     team_files: list<string>,
 *  } $paths
 */
$paths = require __DIR__.'/chisel-paths.php';

return Chisel::script(__DIR__)
    ->questions([
        Question::multiselect(
            name: 'auth_features',
            label: 'Which authentication features would you like to enable?',
            options: [
                'email-verification' => 'Email verification',
                'registration' => 'Registration',
                'password-reset' => 'Password reset',
                '2fa' => 'Two-factor authentication',
                'passkeys' => 'Passkeys',
                'password-confirmation' => 'Password confirmation',
            ],
            default: ['email-verification', 'registration', 'password-reset', '2fa', 'passkeys', 'password-confirmation'],
            hint: 'Use space to select, enter to confirm.',
        ),
        Question::multiselect(
            name: 'application_features',
            label: 'Which application features would you like to include?',
            options: [
                'teams' => 'Teams and invitations',
                'oauth-api' => 'Passport OAuth2 API',
                'mcp' => 'Application MCP server scaffolding',
            ],
            default: ['teams', 'oauth-api', 'mcp'],
            hint: 'Use space to select, enter to confirm.',
        ),
        Question::multiselect(
            name: 'development_features',
            label: 'Which development tools would you like to include?',
            options: [
                'octane' => 'Octane with FrankenPHP',
                'browser-testing' => 'Pest browser testing',
                'ai-tooling' => 'Boost and AI agent tooling',
                'ide-helper' => 'Laravel IDE Helper',
                'git-hooks' => 'Whisky Git hooks',
                'herd' => 'Herd HTTPS and proxy setup',
            ],
            default: ['octane', 'browser-testing', 'ai-tooling', 'ide-helper', 'git-hooks', 'herd'],
            hint: 'Use space to select, enter to confirm.',
        ),
    ])
    ->selected(
        'auth_features',
        'registration',
        then: function (Chisel $c) use ($paths) {
            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                $paths['login'],
                $paths['welcome'],
            )->removeSectionMarkers('registration');
        },
        else: function (Chisel $c) use ($paths) {
            $c->file('config/fortify.php')->removeSection('registration');

            $c->files(
                'app/Providers/FortifyServiceProvider.php',
                $paths['login'],
                $paths['welcome'],
            )->removeSection('registration');

            $c->files(
                'app/Actions/Fortify/CreateNewUser.php',
                'app/Http/Responses/RegisterResponse.php',
                $paths['register'],
                'tests/Feature/Auth/RegistrationTest.php',
            )->delete();
        },
    )
    ->selected(
        'auth_features',
        'password-reset',
        then: function (Chisel $c) use ($paths) {
            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'database/migrations/0001_01_01_000000_create_users_table.php',
                $paths['login'],
            )->removeSectionMarkers('password-reset');
        },
        else: function (Chisel $c) use ($paths) {
            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'database/migrations/0001_01_01_000000_create_users_table.php',
                $paths['login'],
            )->removeSection('password-reset');

            $c->files(
                'app/Actions/Fortify/ResetUserPassword.php',
                'resources/js/pages/auth/ForgotPassword.vue',
                'resources/js/pages/auth/ResetPassword.vue',
                'tests/Feature/Auth/PasswordResetTest.php',
            )->delete();
        },
    )
    ->selected(
        'auth_features',
        'email-verification',
        then: function (Chisel $c) use ($paths) {
            $c->files(
                'config/fortify.php',
                $paths['profile'],
                'app/Providers/FortifyServiceProvider.php',
            )->removeSectionMarkers('email-verification');
        },
        else: function (Chisel $c) use ($paths) {
            $c->php('app/Models/User.php')
                ->removeImport('Illuminate\Contracts\Auth\MustVerifyEmail')
                ->removeInterface('MustVerifyEmail');

            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                $paths['profile'],
            )->removeSection('email-verification');

            $c->files(
                'app/Http/Responses/VerifyEmailResponse.php',
                $paths['verify_email'],
                'tests/Feature/Auth/EmailVerificationTest.php',
                'tests/Feature/Auth/VerificationNotificationTest.php',
            )->delete();
        },
    )
    ->selected(
        'auth_features',
        '2fa',
        then: function (Chisel $c) use ($paths) {
            $c->files(
                'app/Models/User.php',
                'database/factories/UserFactory.php',
                $paths['security'],
                $paths['auth_types'],
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
                'tests/Feature/Auth/AuthenticationTest.php',
                'tests/Feature/Settings/SecurityTest.php',
            )->removeSectionMarkers('2fa');
        },
        else: function (Chisel $c) use ($paths) {
            $c->php('app/Models/User.php')
                ->removeImport('Laravel\Fortify\TwoFactorAuthenticatable')
                ->removeTrait('TwoFactorAuthenticatable');

            $c->files(
                'app/Models/User.php',
                'database/factories/UserFactory.php',
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
                'tests/Feature/Auth/AuthenticationTest.php',
                'tests/Feature/Settings/SecurityTest.php',
                $paths['security'],
                $paths['auth_types'],
            )->removeSection('2fa');

            $c->file('app/Models/User.php')
                ->removeLinesContaining('@property string|null $two_factor_secret')
                ->removeLinesContaining('@property string|null $two_factor_recovery_codes')
                ->removeLinesContaining('@property Carbon|null $two_factor_confirmed_at')
                ->replace(", 'two_factor_secret', 'two_factor_recovery_codes'", '');

            $c->file('app/Http/Controllers/Settings/SecurityController.php')
                ->replace(
                    'use App\\Http\\Requests\\Settings\\TwoFactorAuthenticationRequest;',
                    'use Illuminate\\Http\\Request;',
                )
                ->replace(
                    'edit(TwoFactorAuthenticationRequest $request)',
                    'edit(Request $request)',
                );

            $c->files(...[
                $paths['two_factor_challenge'],
                ...$paths['two_factor_files'],
                'database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php',
                'app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php',
                'app/Http/Responses/TwoFactorLoginResponse.php',
                'tests/Feature/Auth/TwoFactorChallengeTest.php',
            ])->delete();
        },
    )
    ->selected(
        'auth_features',
        'passkeys',
        then: function (Chisel $c) use ($paths) {
            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
                'routes/settings.php',
                'tests/Feature/Auth/AuthenticationTest.php',
                'tests/Feature/Settings/SecurityTest.php',
                $paths['auth_types'],
                $paths['security'],
                $paths['login'],
                $paths['confirm_password'],
            )->removeSectionMarkers('passkeys');
        },
        else: function (Chisel $c) use ($paths) {
            $c->php('app/Models/User.php')
                ->removeImport('Laravel\Fortify\PasskeyAuthenticatable')
                ->removeImport('Laravel\Fortify\Contracts\PasskeyUser')
                ->removeTrait('PasskeyAuthenticatable')
                ->removeInterface('PasskeyUser');

            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
                'routes/settings.php',
                'tests/Feature/Auth/AuthenticationTest.php',
                'tests/Feature/Settings/SecurityTest.php',
                $paths['auth_types'],
                $paths['security'],
                $paths['login'],
                $paths['confirm_password'],
            )->removeSection('passkeys');

            $c->files(...[
                ...$paths['passkey_files'],
                'app/Http/Responses/PasskeyLoginResponse.php',
                'database/migrations/2024_01_01_000000_create_passkeys_table.php',
            ])->delete();
        },
    )
    ->selected(
        'auth_features',
        'password-confirmation',
        then: function (Chisel $c) {
            $c->files(
                'app/Providers/FortifyServiceProvider.php',
                'routes/settings.php',
                'tests/Feature/Settings/SecurityTest.php',
            )->removeSectionMarkers('password-confirmation');
        },
        else: function (Chisel $c) use ($paths) {
            $c->file('config/fortify.php')
                ->replace("'confirmPassword' => true,", "'confirmPassword' => false,");

            $c->files(
                'app/Providers/FortifyServiceProvider.php',
                'routes/settings.php',
                'tests/Feature/Settings/SecurityTest.php',
            )->removeSection('password-confirmation');

            $c->files(
                $paths['confirm_password'],
                'tests/Feature/Auth/PasswordConfirmationTest.php',
            )->delete();
        },
    )
    ->selected(
        'application_features',
        'teams',
        then: function (Chisel $c) {
            $c->files(
                'app/Actions/Fortify/CreateNewUser.php',
                'app/Models/User.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Middleware/HandleInertiaRequests.php',
                'bootstrap/app.php',
                'database/factories/UserFactory.php',
                'routes/web.php',
                'routes/settings.php',
                'routes/console.php',
                'tests/Feature/Auth/AuthenticationTest.php',
                'tests/Feature/Auth/EmailVerificationTest.php',
                'tests/Feature/Auth/RegistrationTest.php',
                'tests/Feature/DashboardTest.php',
                'resources/js/app.ts',
                'resources/js/components/AppHeader.vue',
                'resources/js/components/AppSidebar.vue',
                'resources/js/components/NavUser.vue',
                'resources/js/components/UserInfo.vue',
                'resources/js/layouts/settings/Layout.vue',
                'resources/js/pages/Dashboard.vue',
                'resources/js/pages/Welcome.vue',
                'resources/js/pages/auth/Login.vue',
                'resources/js/pages/auth/Register.vue',
                'resources/js/types/global.d.ts',
                'resources/js/types/index.ts',
            )->removeSectionMarkers('teams');

            $c->file('routes/web.php')->removeLinesContaining('@chisel-no-teams-dashboard-route');
        },
        else: function (Chisel $c) use ($paths) {
            $c->php('app/Models/User.php')
                ->removeImport('App\Concerns\HasTeams')
                ->removeTrait('HasTeams');

            $c->files(
                'app/Actions/Fortify/CreateNewUser.php',
                'app/Models/User.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Middleware/HandleInertiaRequests.php',
                'bootstrap/app.php',
                'database/factories/UserFactory.php',
                'routes/web.php',
                'routes/settings.php',
                'routes/console.php',
                'tests/Feature/Auth/AuthenticationTest.php',
                'tests/Feature/Auth/EmailVerificationTest.php',
                'tests/Feature/Auth/RegistrationTest.php',
                'tests/Feature/DashboardTest.php',
                'resources/js/app.ts',
                'resources/js/components/AppHeader.vue',
                'resources/js/components/AppSidebar.vue',
                'resources/js/components/NavUser.vue',
                'resources/js/components/UserInfo.vue',
                'resources/js/layouts/settings/Layout.vue',
                'resources/js/pages/Dashboard.vue',
                'resources/js/pages/Welcome.vue',
                'resources/js/pages/auth/Login.vue',
                'resources/js/pages/auth/Register.vue',
                'resources/js/types/global.d.ts',
                'resources/js/types/index.ts',
            )->removeSection('teams');

            $c->file('app/Actions/Fortify/CreateNewUser.php')->replace(
                "        return DB::transaction(function () use (\$input) {\n            \$user = User::query()->create([\n                'name' => \$input['name'],\n                'email' => \$input['email'],\n                'password' => \$input['password'],\n            ]);\n\n            \$this->createTeam->handle(\$user, \$user->name.\"'s Team\", isPersonal: true);\n\n            return \$user;\n        });",
                "        return User::query()->create([\n            'name' => \$input['name'],\n            'email' => \$input['email'],\n            'password' => \$input['password'],\n        ]);",
            );

            $c->file('routes/web.php')->replace(
                '// @chisel-no-teams-dashboard-route',
                "Route::inertia('dashboard', 'Dashboard')\n    ->middleware(['auth', 'verified'])\n    ->name('dashboard');",
            );

            foreach (['resources/js/components/AppHeader.vue', 'resources/js/components/AppSidebar.vue', 'resources/js/pages/Welcome.vue'] as $file) {
                $c->file($file)->replace(
                    "const dashboardUrl = computed(() =>\n    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',\n);",
                    'const dashboardUrl = computed(() => dashboard().url);',
                );
            }

            $c->file('resources/js/components/AppSidebar.vue')
                ->replace("import { Link, usePage } from '@inertiajs/vue3';", "import { Link } from '@inertiajs/vue3';");

            $c->file('resources/js/pages/Welcome.vue')
                ->replace("import { Head, Link, usePage } from '@inertiajs/vue3';", "import { Head, Link } from '@inertiajs/vue3';");

            $c->file('resources/js/components/NavUser.vue')
                ->replace("import { computed } from 'vue';\n", '')
                ->replace('<UserInfo :user="user" :team="currentTeam" />', '<UserInfo :user="user" />');

            $c->file('resources/js/pages/Dashboard.vue')->replace(
                "layout: (props: { currentTeam?: Team | null }) => ({\n        breadcrumbs: [\n            {\n                title: 'Dashboard',\n                href: props.currentTeam\n                    ? dashboard(props.currentTeam.slug)\n                    : '/',\n            },\n        ],\n    }),",
                "layout: {\n        breadcrumbs: [\n            {\n                title: 'Dashboard',\n                href: dashboard(),\n            },\n        ],\n    },",
            );

            $c->file('resources/js/pages/auth/Login.vue')->replace(
                "register({\n                        query: {\n                            invitation: teamInvitation?.code,\n                        },\n                    })",
                'register()',
            );

            $c->file('resources/js/pages/auth/Register.vue')->replace(
                "teamInvitation\n                        ? login.url({\n                              query: {\n                                  invitation: teamInvitation.code,\n                              },\n                          })\n                        : login()",
                'login()',
            );

            $c->file('resources/js/components/UserInfo.vue')
                ->replace('v-else-if="showEmail"', 'v-if="showEmail"');

            $c->file('tests/Feature/Auth/EmailVerificationTest.php')
                ->replace('->assertRedirect("/{$team->slug}/dashboard?verified=1")', "->assertRedirect('/dashboard?verified=1')");

            $c->file('app/Models/User.php')
                ->removeLinesContaining('@property int|null $current_team_id')
                ->removeLinesContaining('@property-read Team|null $currentTeam')
                ->removeLinesContaining('@property-read Collection<int, Team> $ownedTeams')
                ->removeLinesContaining('@property-read Collection<int, Membership> $teamMemberships')
                ->removeLinesContaining('@property-read Collection<int, Team> $teams')
                ->replace(", 'current_team_id'", '');

            $c->files(
                'app/Http/Controllers/DashboardController.php',
                'app/Http/Responses/Concerns/RedirectsToCurrentTeam.php',
                'app/Http/Responses/LoginResponse.php',
                'app/Http/Responses/RegisterResponse.php',
                'app/Http/Responses/VerifyEmailResponse.php',
                'app/Http/Responses/TwoFactorLoginResponse.php',
                'app/Http/Responses/PasskeyLoginResponse.php',
                ...$paths['team_files'],
            )->delete();
        },
    )
    ->selected(
        'application_features',
        'oauth-api',
        then: function (Chisel $c) {
            $c->files(
                'app/Models/User.php',
                'app/Providers/AppServiceProvider.php',
                'bootstrap/app.php',
                'config/auth.php',
                'resources/js/app.ts',
            )->removeSectionMarkers('oauth-api');
        },
        else: function (Chisel $c) {
            $c->php('app/Models/User.php')
                ->removeImport('Laravel\Passport\Contracts\OAuthenticatable')
                ->removeImport('Laravel\Passport\HasApiTokens')
                ->removeTrait('HasApiTokens')
                ->removeInterface('OAuthenticatable');

            $c->files(
                'app/Models/User.php',
                'app/Providers/AppServiceProvider.php',
                'bootstrap/app.php',
                'config/auth.php',
                'resources/js/app.ts',
            )->removeSection('oauth-api');

            $c->file('composer.json')
                ->removeLinesContaining('artisan passport:keys');

            $c->files(
                'config/passport.php',
                'routes/api.php',
                'resources/js/pages/auth/OAuthConsent.vue',
                'storage/oauth-private.key',
                'storage/oauth-public.key',
                'database/migrations/2026_06_13_213702_create_oauth_auth_codes_table.php',
                'database/migrations/2026_06_13_213703_create_oauth_access_tokens_table.php',
                'database/migrations/2026_06_13_213704_create_oauth_refresh_tokens_table.php',
                'database/migrations/2026_06_13_213705_create_oauth_clients_table.php',
                'database/migrations/2026_06_13_213706_create_oauth_device_codes_table.php',
            )->delete();
        },
    )
    ->selected(
        'application_features',
        'mcp',
        else: function (Chisel $c) {
            $c->file('composer.json')->replace(
                "        \"mcp:inspect\": [\n            \"Composer\\\\Config::disableProcessTimeout\",\n            \"NODE_OPTIONS=--use-system-ca npx @mcpjam/inspector@latest\"\n        ],\n",
                '',
            );

            $c->files(
                'routes/ai.php',
                'resources/views/vendor/mcp/components/app.blade.php',
            )->delete();
        },
    )
    ->selected(
        'development_features',
        'octane',
        then: function (Chisel $c) {
            $c->files(
                'app/Providers/AppServiceProvider.php',
                'config/cache.php',
                'tests/Feature/StarterKitConfigurationTest.php',
            )->removeSectionMarkers('octane');
        },
        else: function (Chisel $c) {
            $c->files(
                'app/Providers/AppServiceProvider.php',
                'config/cache.php',
                'tests/Feature/StarterKitConfigurationTest.php',
            )->removeSection('octane');

            $c->file('composer.json')
                ->replace(
                    "        \"setup:octane\": [\n            \"@php artisan octane:install --server=frankenphp --no-interaction\"\n        ],\n",
                    '',
                )
                ->removeLinesContaining('"@setup:octane"')
                ->replace('php artisan octane:start --watch', 'php artisan serve');

            $c->file('.gitignore')
                ->removeLinesContaining('frankenphp');

            $c->files('config/octane.php', 'public/frankenphp-worker.php')->delete();
        },
    )
    ->selected(
        'development_features',
        'browser-testing',
        then: function (Chisel $c) {
            $c->file('phpunit.xml')->removeSectionMarkers('browser-testing');
        },
        else: function (Chisel $c) {
            $c->file('composer.json')->removeLinesContaining('npx playwright install');
            $c->file('tests/Pest.php')->replace("->in('Feature', 'Browser');", "->in('Feature');");
            $c->file('phpunit.xml')->removeSection('browser-testing');
            $c->file('.gitignore')->removeLinesContaining('/tests/Browser/Screenshots');
            $c->file('.github/workflows/tests.yml')->replace(
                "            - name: Install Playwright Browsers\n              run: npx playwright install --with-deps\n\n",
                '',
            );
            $c->files('tests/Browser/ExampleTest.php')->delete();
        },
    )
    ->selected(
        'development_features',
        'ai-tooling',
        else: function (Chisel $c) {
            $c->file('composer.json')->replace(
                "            \"@php artisan vendor:publish --tag=laravel-assets --ansi --force\",\n            \"@php artisan boost:update --env=local --ansi\"",
                '            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"',
            );
            $c->files('AGENTS.md', 'CLAUDE.md', 'boost.json', '.mcp.json', '.vscode/mcp.json')->delete();

            foreach (['.agents', '.ai', '.claude', '.codex', '.github/skills'] as $directory) {
                chiselDeleteDirectory($directory);
            }

        },
    )
    ->selected(
        'development_features',
        'ide-helper',
        else: function (Chisel $c) {
            $c->file('composer.json')->removeLinesContaining('ide-helper:generate');
            $c->files('_ide_helper.php')->delete();
        },
    )
    ->selected(
        'development_features',
        'git-hooks',
        else: function (Chisel $c) {
            $c->file('composer.json')->removeLinesContaining('vendor/bin/whisky');
            $c->files('whisky.json')->delete();
        },
    )
    ->selected(
        'development_features',
        'herd',
        else: function (Chisel $c) {
            $c->file('composer.json')
                ->removeLinesContaining('herd secure')
                ->removeLinesContaining('herd proxy');
        },
    )
    ->apply(function (Chisel $c, array $answers) use ($paths): void {
        $composerPackages = [];
        $composerDevPackages = [];
        $npmPackages = [];

        if (! chiselSelected($answers, 'auth_features', '2fa') && $paths['two_factor_otp_package'] !== null) {
            $npmPackages[] = $paths['two_factor_otp_package'];
        }

        if (! chiselSelected($answers, 'auth_features', 'passkeys')) {
            $npmPackages[] = '@laravel/passkeys';
        }

        if (! chiselSelected($answers, 'application_features', 'oauth-api')) {
            $composerPackages[] = 'laravel/passport';
        }

        if (! chiselSelected($answers, 'application_features', 'mcp')) {
            // Boost declares its own laravel/mcp dependency, so the app-level
            // requirement can go even when the AI tooling stays.
            $composerPackages[] = 'laravel/mcp';
        }

        if (! chiselSelected($answers, 'development_features', 'octane')) {
            $composerPackages[] = 'laravel/octane';
            $npmPackages[] = 'chokidar';
        }

        if (! chiselSelected($answers, 'development_features', 'browser-testing')) {
            $composerDevPackages[] = 'pestphp/pest-plugin-browser';
            $npmPackages[] = 'playwright';
        }

        if (! chiselSelected($answers, 'development_features', 'ai-tooling')) {
            $composerDevPackages[] = 'laravel/boost';
            $composerDevPackages[] = 'laravel/pao';
        }

        if (! chiselSelected($answers, 'development_features', 'ide-helper')) {
            $composerDevPackages[] = 'barryvdh/laravel-ide-helper';
        }

        if (! chiselSelected($answers, 'development_features', 'git-hooks')) {
            $composerDevPackages[] = 'projektgopher/whisky';
        }

        $c->file('composer.json')
            ->replace(
                "            \"@php artisan boost:update --env=local --ansi\",\n            \"@php artisan install:features --ansi\"",
                '            "@php artisan boost:update --env=local --ansi"',
            )
            ->replace(
                "            \"@php artisan vendor:publish --tag=laravel-assets --ansi --force\",\n            \"@php artisan install:features --ansi\"",
                '            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"',
            )
            ->replace(
                "            \"installer\": {\n                \"post-create-project\": [\n                    \"@php artisan install:features --ansi\"\n                ]\n            }",
                "            \"installer\": {\n                \"post-create-project\": []\n            }",
            );

        if ($npmPackages !== []) {
            chiselRemoveNpmPackages($c, ...$npmPackages);
        }

        chiselRemoveComposerPackages($composerDevPackages, dev: true);
        chiselRemoveComposerPackages($composerPackages);
        chiselRun(['composer', 'dump-autoload', '--no-interaction'], 'Refresh Composer Autoload');

        // The installer migrates before Chisel deletes deselected migrations, so
        // rebuild the schema from the surviving ones. The clone flow has no
        // database yet at this point; its later migrate only sees survivors.
        if (file_exists(__DIR__.'/database/database.sqlite')) {
            chiselRun(['php', 'artisan', 'migrate:fresh', '--force', '--no-interaction'], 'Rebuild Database Schema');
        }

        chiselRun(['composer', 'lint'], 'Composer Lint');
        chiselRun(['php', 'artisan', 'wayfinder:generate', '--with-form', '--no-interaction'], 'Generate Wayfinder Resources');

        if (! chiselSkipsNode()) {
            if (chiselSelected($answers, 'development_features', 'browser-testing')) {
                chiselRun(['npx', 'playwright', 'install'], 'Install Playwright Browsers');
            }

            $c->npm()->run('lint');
            $c->npm()->run('format');
        }

        if (file_exists(__DIR__.'/composer.lock')) {
            chiselRun(
                ['composer', 'update', '--lock', '--no-install', '--no-scripts', '--no-interaction'],
                'Refresh Composer Lock',
            );
        }

        $c->files(
            'app/Console/Commands/InstallFeaturesCommand.php',
            'chisel.php',
            'chisel-paths.php',
            'tests/Feature/StarterKitConfigurationTest.php',
        )->delete();

        // Chisel's classes are loaded in memory while this script runs, so the
        // toolkit must be removed last, after every consumer above is gone.
        chiselRun([
            'composer',
            'remove',
            'laravel/chisel',
            '--no-interaction',
            '--no-scripts',
            '--no-audit',
            '--minimal-changes',
        ], 'Remove Laravel Chisel');
    });
