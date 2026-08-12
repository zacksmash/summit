<?php

namespace App\Providers;

/* @chisel-registration */
use App\Actions\Fortify\CreateNewUser;
/* @end-chisel-registration */
/* @chisel-password-reset */
use App\Actions\Fortify\ResetUserPassword;
/* @end-chisel-password-reset */
/* @chisel-teams */
use App\Http\Responses\LoginResponse;
/* @end-chisel-teams */
/* @chisel-passkeys */
/* @chisel-teams */
use App\Http\Responses\PasskeyLoginResponse;
/* @end-chisel-teams */
/* @end-chisel-passkeys */
/* @chisel-registration */
/* @chisel-teams */
use App\Http\Responses\RegisterResponse;
/* @end-chisel-teams */
/* @end-chisel-registration */
/* @chisel-2fa */
/* @chisel-teams */
use App\Http\Responses\TwoFactorLoginResponse;
/* @end-chisel-teams */
/* @end-chisel-2fa */
/* @chisel-email-verification */
/* @chisel-teams */
use App\Http\Responses\VerifyEmailResponse;
/* @end-chisel-teams */
/* @end-chisel-email-verification */
/* @chisel-teams */
use App\Models\TeamInvitation;
/* @end-chisel-teams */
use Illuminate\Cache\RateLimiting\Limit;
/* @chisel-teams */
use Illuminate\Contracts\Database\Query\Builder;
/* @end-chisel-teams */
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
/* @chisel-teams */
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
/* @end-chisel-teams */
/* @chisel-registration */
/* @chisel-teams */
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
/* @end-chisel-teams */
/* @end-chisel-registration */
/* @chisel-2fa */
/* @chisel-teams */
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
/* @end-chisel-teams */
/* @end-chisel-2fa */
/* @chisel-email-verification */
/* @chisel-teams */
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
/* @end-chisel-teams */
/* @end-chisel-email-verification */
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
/* @chisel-passkeys */
/* @chisel-teams */
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;

/* @end-chisel-teams */

/* @end-chisel-passkeys */

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        /* @chisel-teams */
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        /* @end-chisel-teams */
        /* @chisel-passkeys */
        /* @chisel-teams */
        $this->app->singleton(PasskeyLoginResponseContract::class, PasskeyLoginResponse::class);
        /* @end-chisel-teams */
        /* @end-chisel-passkeys */
        /* @chisel-registration */
        /* @chisel-teams */
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        /* @end-chisel-teams */
        /* @end-chisel-registration */
        /* @chisel-2fa */
        /* @chisel-teams */
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
        /* @end-chisel-teams */
        /* @end-chisel-2fa */
        /* @chisel-email-verification */
        /* @chisel-teams */
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
        /* @end-chisel-teams */
        /* @end-chisel-email-verification */
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        /* @chisel-password-reset */
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        /* @end-chisel-password-reset */
        /* @chisel-registration */
        Fortify::createUsersUsing(CreateNewUser::class);
        /* @end-chisel-registration */
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            /* @chisel-password-reset */
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            /* @end-chisel-password-reset */
            'status' => $request->session()->get('status'),
            /* @chisel-teams */
            'teamInvitation' => $this->teamInvitation($request),
            /* @end-chisel-teams */
        ]));

        /* @chisel-password-reset */
        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));
        /* @end-chisel-password-reset */

        /* @chisel-email-verification */
        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));
        /* @end-chisel-email-verification */

        /* @chisel-registration */
        Fortify::registerView(fn (Request $request) => Inertia::render('auth/Register', [
            /* @chisel-teams */
            'teamInvitation' => $this->teamInvitation($request),
            /* @end-chisel-teams */
        ]));
        /* @end-chisel-registration */

        /* @chisel-2fa */
        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));
        /* @end-chisel-2fa */

        /* @chisel-password-confirmation */
        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
        /* @end-chisel-password-confirmation */
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        /* @chisel-2fa */
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
        /* @end-chisel-2fa */

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        /* @chisel-passkeys */
        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
        /* @end-chisel-passkeys */
    }

    /* @chisel-teams */
    /**
     * Get the pending team invitation context for auth pages.
     *
     * @return array{code: string, teamName: string}|null
     */
    private function teamInvitation(Request $request): ?array
    {
        $invitationCode = $request->query('invitation');

        if (! is_string($invitationCode)) {
            return null;
        }

        $invitation = TeamInvitation::query()
            ->with('team')
            ->where('code', $invitationCode)
            ->whereNull('accepted_at')
            ->where(fn (Builder $query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->first();

        if (! $invitation) {
            return null;
        }

        return [
            'code' => $invitation->code,
            'teamName' => $invitation->team->name,
        ];
    }
    /* @end-chisel-teams */
}
