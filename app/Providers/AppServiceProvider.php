<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;

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
        $this->configureDefaults();

        $this->configureMcpAuthorizationView();
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
     * Configure the Passport authoriztion view for the MCP server
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
