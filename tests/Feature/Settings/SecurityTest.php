<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

test('security page is displayed', function () {
    /* @chisel-2fa */
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);
    /* @end-chisel-2fa */
    /* @chisel-passkeys */
    Features::passkeys([
        'confirmPassword' => true,
    ]);
    /* @end-chisel-passkeys */

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        /* @chisel-password-confirmation */
        ->withSession(['auth.password_confirmed_at' => time()])
        /* @end-chisel-password-confirmation */
        ->get(route('security.edit'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('settings/Security'),
    );

    /* @chisel-passkeys */
    $response->assertInertia(fn (Assert $page) => $page
        ->where('canManagePasskeys', true)
        ->where('passkeys', []),
    );
    /* @end-chisel-passkeys */

    /* @chisel-2fa */
    $response->assertInertia(fn (Assert $page) => $page
        ->where('canManageTwoFactor', true)
        ->where('twoFactorEnabled', false),
    );
    /* @end-chisel-2fa */
});

/* @chisel-password-confirmation */
test('security page requires password confirmation when enabled', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('security.edit'));

    $response->assertRedirect(route('password.confirm'));
});
/* @end-chisel-password-confirmation */

/* @chisel-2fa */
test('security page renders without two factor when feature is disabled', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    config(['fortify.features' => []]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        /* @chisel-password-confirmation */
        ->withSession(['auth.password_confirmed_at' => time()])
        /* @end-chisel-password-confirmation */
        ->get(route('security.edit'))
        ->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('settings/Security')
        ->where('canManageTwoFactor', false)
        ->missing('twoFactorEnabled')
        ->missing('requiresConfirmation'),
    );
});
/* @end-chisel-2fa */

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('security.edit'));
});
