<?php

use App\Models\User;

it('may sign in the user', function () {
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $page = visit('/');

    $page->click('Log in')
        ->assertPathIs('/login')
        ->assertSee('Log in to your account')
        ->fill('email', 'john@example.com')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertSee('Dashboard');

    $this->assertAuthenticated();
});
