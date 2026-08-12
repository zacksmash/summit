<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\ExecutableFinder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerDevCommands();

        $this->configureDefaults();

        $this->configureMcpAuthorizationView();
    }

    /**
     * Register development commands for the "dev" Artisan command.
     */
    protected function registerDevCommands(): void
    {
        if (! $this->octaneServerIsAvailable()) {
            DevCommands::artisan('serve', 'server');
        }
    }

    /**
     * Determine whether the configured Octane server can run locally.
     */
    protected function octaneServerIsAvailable(): bool
    {
        return match ((string) config('octane.server')) {
            'frankenphp' => (new ExecutableFinder)->find('frankenphp', null, [base_path()]) !== null,
            'roadrunner' => (new ExecutableFinder)->find('rr', null, [base_path()]) !== null,
            'swoole' => extension_loaded('swoole') || extension_loaded('openswoole'),
            default => false,
        };
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure the Passport authorization view for the MCP server
     */
    public function configureMcpAuthorizationView(): void
    {
        Passport::authorizationView(
            fn (array $parameters): Response => Inertia::render('auth/OAuthConsent', [
                'client' => [
                    'id' => $parameters['client']->getKey(),
                    'name' => $parameters['client']->name,
                ],
                'user' => [
                    'email' => $parameters['user']->email,
                ],
                'scopes' => $parameters['scopes'],
                'authToken' => $parameters['authToken'],
                'csrf' => csrf_token(),
            ])->toResponse(request())
        );
    }
}
