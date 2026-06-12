---
name: pest-browser-testing
description: Comprehensive skill for writing, configuring, and debugging PestPHP browser tests using the pest-plugin-browser (Playwright) integration. Covers test anatomy, selectors, interactions, assertions, Laravel integration, CI setup, and best practices for robust browser testing.
---

# PestPHP Browser Testing Skill

## Overview

Pest v4 introduced first-class browser testing via `pest-plugin-browser`, powered by **Playwright** under the hood. This replaces Dusk for Pest users. Browser tests integrate seamlessly with Laravel's testing API (RefreshDatabase, Event faking, authentication assertions) while running real browser interactions.

This skill covers everything needed to write, configure, and debug Pest browser tests at a production level.

---

## Installation & Setup

### 1. Install the plugin

```bash
composer require pestphp/pest-plugin-browser --dev

npm install playwright@latest
npx playwright install
```

### 2. Add to .gitignore

```
tests/Browser/Screenshots
```

### 3. Configure tests/Pest.php for browser tests

```php
// Apply DuskTestCase (or your base) and RefreshDatabase to the Browser folder
pest()
    ->extend(\Tests\DuskTestCase::class)
    ->use(\Illuminate\Foundation\Testing\DatabaseMigrations::class)
    ->in('Browser');
```

---

## Running Browser Tests

```bash
# Run all tests (browser tests auto-detected)
./vendor/bin/pest

# Run in parallel (recommended for speed)
./vendor/bin/pest --parallel

# Run with headed browser (visible window)
./vendor/bin/pest --headed

# Run in debug mode (headed, pauses on failure)
./vendor/bin/pest --debug

# Run only browser group
./vendor/bin/pest --group=browser

# Use a specific browser
./vendor/bin/pest --browser firefox
./vendor/bin/pest --browser safari

# Test sharding for CI (split across multiple machines)
./vendor/bin/pest --shard=1/3
./vendor/bin/pest --shard=2/3 --parallel
```

---

## File Placement

Browser tests live in `tests/Browser/`. Name files with `Test.php` suffix:

```
tests/
  Browser/
    AuthTest.php
    CheckoutTest.php
    DashboardTest.php
    Screenshots/   ← gitignored
```

---

## Basic Test Anatomy

```php
<?php

it('may welcome the user', function () {
    $page = visit('/');

    $page->assertSee('Welcome');
});

it('may sign in the user', function () {
    Event::fake();

    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $page = visit('/login');

    $page->fill('email', 'user@example.com')
         ->fill('password', 'password')
         ->press('Sign In')
         ->assertSee('Dashboard')
         ->assertPathIs('/dashboard');

    $this->assertAuthenticated();
    Event::assertDispatched(UserLoggedIn::class);
});
```

> **Key principle:** `$this` inside browser test closures gives access to PHPUnit/Laravel assertion methods, while `$page` gives access to browser interaction methods.

---

## Navigation

### visit()

```php
// Single page
$page = visit('/');
$page = visit('/dashboard');
$page = visit(route('login'));

// Multiple pages at once
$pages = visit(['/', '/about', '/contact']);
[$home, $about, $contact] = $pages;
```

### navigate()

Navigate to another URL within the same browser context:

```php
$page = visit('/');
$page->navigate('/about')->assertSee('About Us');
```

---

## Browser & Device Configuration

### Per-test browser/device overrides

```php
// Firefox on mobile
$page = visit('/')->on()->mobile()->firefox();

// Specific device
$page = visit('/')->on()->iPhone14Pro();
$page = visit('/')->on()->macbook14();

// Dark mode
$page = visit('/')->inDarkMode();
```

### Per-test context options

```php
$page = visit('/')->withLocale('fr-FR');
$page = visit('/')->withTimezone('America/New_York');
$page = visit('/')->withUserAgent('Googlebot');
$page = visit('/dashboard')->withHost('tenant.localhost');
$page = visit('/')->geolocation(39.399872, -8.224454);
```

### Global Pest.php configuration

```php
// Default browser
pest()->browser()->inFirefox();
pest()->browser()->inSafari();

// Always headed
pest()->browser()->headed();

// Timeout (milliseconds, default 5000)
pest()->browser()->timeout(10000);

// Custom user agent for all tests
pest()->browser()->userAgent('CustomBot/1.0');

// Custom host for subdomain apps
pest()->browser()->withHost('app.localhost');
```

---

## Element Selectors

Pest supports multiple selector strategies in most interaction methods:

```php
// Text content (finds element containing this text)
$page->click('Login');

// CSS class
$page->click('.btn-primary');

// CSS ID
$page->click('#submit-button');

// Data-test attribute (@ prefix = shorthand for [data-test="..."])
$page->click('@login-button');

// Any valid CSS selector
$page->click('button[type="submit"]');
$page->click('input[name="email"]');
$page->assertVisible('table tr:first-child');
```

---

## Element Interactions

### Typing & Input

```php
// Type into a field (field name, id, label, or selector)
$page->fill('email', 'user@example.com');  // preferred for forms
$page->type('email', 'user@example.com');  // same as fill

// Type slowly (simulates real user keystroke timing)
$page->typeSlowly('search', 'query text');

// Append text without clearing the field
$page->append('description', ' appended content');

// Clear a field
$page->clear('search');
```

### Keyboard

```php
// Send raw key sequences
$page->keys('input[name=password]', 'secret');

// Keyboard shortcuts
$page->keys('input', ['{Control}', 'a']);  // Ctrl+A

// Hold a modifier key while performing actions
$page->withKeyDown('Shift', function () use ($page): void {
    $page->keys('#input', ['KeyA', 'KeyB', 'KeyC']); // types "ABC"
});
// Note: Use key codes (KeyA) not characters ('a') when modifier keys are involved
```

### Clicking

```php
$page->click('Login');              // by text
$page->click('.btn-primary');       // by class
$page->click('#submit');            // by ID
$page->click('@login');             // by data-test attribute

// Click with options
$page->click('#button', options: ['clickCount' => 2]); // double-click
```

### Buttons

```php
// Press a button by text or name attribute
$page->press('Submit');

// Press and wait N seconds
$page->pressAndWaitFor('Submit', 2);
```

### Forms

```php
// Select dropdown
$page->select('country', 'US');
$page->select('interests', ['music', 'sports']); // multi-select

// Radio buttons
$page->radio('size', 'large');

// Checkboxes
$page->check('terms');
$page->check('color', 'blue');     // checkbox with specific value
$page->uncheck('newsletter');
$page->uncheck('color', 'red');

// Submit the first form on the page
$page->submit();

// File upload
$page->attach('avatar', '/absolute/path/to/image.jpg');
```

### Mouse

```php
// Hover over element
$page->hover('#dropdown-trigger');

// Drag element to another element
$page->drag('#item', '#dropzone');
```

### iFrames

```php
use Pest\Browser\Api\PendingAwaitablePage;

$page->withinIframe('.iframe-container', function (PendingAwaitablePage $iframe) {
    $iframe->type('input-inside-frame', 'Hello!')
           ->click('Submit');
});
```

### Page Control

```php
// Wait N seconds
$page->wait(2);

// Wait for a key press (opens browser, useful for manual debugging)
$page->waitForKey();

// Resize browser window
$page->resize(1280, 720);

// Execute JavaScript
$result = $page->script('return document.title');
$page->script('document.querySelector(".modal").remove()');

// Get page content (full HTML)
$html = $page->content();

// Get current URL
$url = $page->url();

// Get element text
$text = $page->text('.header');

// Get element attribute
$alt = $page->attribute('img', 'alt');

// Get input value
$val = $page->value('input[name=email]');
```

---

## Assertions

### Text & Content

```php
$page->assertSee('Welcome to our website');
$page->assertDontSee('Error occurred');

// Scoped to a selector
$page->assertSeeIn('.header', 'Welcome');
$page->assertDontSeeIn('.error-container', 'Error');
$page->assertSeeAnythingIn('.content');      // any text present
$page->assertSeeNothingIn('.empty-widget');  // no text present
```

### Page Title

```php
$page->assertTitle('Home Page');
$page->assertTitleContains('Home');
```

### Element Presence & Visibility

```php
$page->assertPresent('form');           // in DOM (may be hidden)
$page->assertNotPresent('.error-msg'); // not in DOM at all
$page->assertVisible('.alert');        // in DOM AND visible
$page->assertMissing('.hidden-el');    // not visible (may be in DOM)
```

### Count

```php
$page->assertCount('.item', 5); // exactly 5 elements match selector
```

### Source Code

```php
$page->assertSourceHas('<h1>Welcome</h1>');
$page->assertSourceMissing('<div class="error">');
```

### Links

```php
$page->assertSeeLink('About Us');
$page->assertDontSeeLink('Admin Panel');
```

### Form State

```php
// Checkboxes
$page->assertChecked('terms');
$page->assertChecked('color', 'blue');
$page->assertNotChecked('newsletter');
$page->assertNotChecked('color', 'red');
$page->assertIndeterminate('partial-select');

// Radio
$page->assertRadioSelected('size', 'large');
$page->assertRadioNotSelected('size', 'small');

// Dropdown/Select
$page->assertSelected('country', 'US');
$page->assertNotSelected('country', 'UK');

// Input values
$page->assertValue('input[name=email]', 'test@example.com');
$page->assertValueIsNot('input[name=email]', 'wrong@example.com');

// Enabled/disabled
$page->assertEnabled('email');
$page->assertDisabled('submit');
$page->assertButtonEnabled('Save');
$page->assertButtonDisabled('Submit');
```

### Attributes

```php
$page->assertAttribute('img', 'alt', 'Profile Picture');
$page->assertAttributeMissing('button', 'disabled');
$page->assertAttributeContains('div', 'class', 'container');
$page->assertAttributeDoesntContain('div', 'class', 'hidden');
$page->assertAriaAttribute('button', 'label', 'Close');
$page->assertDataAttribute('div', 'id', '123');
```

### URL Assertions

```php
$page->assertUrlIs('https://example.com/home');
$page->assertPathIs('/dashboard');
$page->assertPathIsNot('/login');
$page->assertPathBeginsWith('/users');
$page->assertPathEndsWith('/profile');
$page->assertPathContains('settings');

$page->assertSchemeIs('https');
$page->assertSchemeIsNot('http');
$page->assertHostIs('example.com');
$page->assertHostIsNot('wrong.com');
$page->assertPortIs('443');
$page->assertPortIsNot('8080');

// Query string
$page->assertQueryStringHas('page');
$page->assertQueryStringHas('page', '2');
$page->assertQueryStringMissing('debug');

// URL fragments (#hash)
$page->assertFragmentIs('section-2');
$page->assertFragmentBeginsWith('section');
$page->assertFragmentIsNot('wrong-section');
```

### JavaScript Assertions

```php
// Assert a JS expression evaluates to a specific value
$page->assertScript('document.title', 'Home Page');
$page->assertScript('document.querySelector(".btn").disabled', true);
```

### Health & Quality Assertions

```php
// Assert no console.log output or JS errors
$page->assertNoSmoke();

// Individual checks
$page->assertNoConsoleLogs();
$page->assertNoJavaScriptErrors();

// Accessibility (axe-core powered, default level = 1/serious)
$page->assertNoAccessibilityIssues();
$page->assertNoAccessibilityIssues(level: 0); // critical only
$page->assertNoAccessibilityIssues(level: 2); // moderate+
$page->assertNoAccessibilityIssues(level: 3); // all issues including minor
```

Accessibility levels:
- **0 – Critical**: Severe barriers for disabled users. Legal risk if unaddressed.
- **1 – Serious** *(default)*: Significant impact. Legal risk if unaddressed.
- **2 – Moderate**: Moderately affects accessibility. Not a hard barrier.
- **3 – Minor**: Best practices; minor user experience impact.

### Screenshot Assertion (Visual Regression)

```php
// Assert screenshot matches stored baseline
$page->assertScreenshotMatches();

// Full page, show visual diff on failure
$page->assertScreenshotMatches(true, true);
```

---

## Multi-Page Testing

Useful for smoke testing multiple routes simultaneously:

```php
$pages = visit(['/', '/about', '/contact', '/pricing']);

// Bulk assertions on all pages
$pages->assertNoSmoke()
      ->assertNoAccessibilityIssues()
      ->assertNoConsoleLogs()
      ->assertNoJavaScriptErrors();

// Destructure for individual page assertions
[$home, $about, $contact, $pricing] = $pages;

$home->assertSee('Welcome');
$about->assertSee('Our Team');
$pricing->assertSee('Pro Plan');
```

---

## Debugging

### Debug mode (CLI)

```bash
./vendor/bin/pest --debug    # headed + pauses on failure
./vendor/bin/pest --headed   # just headed, doesn't pause
```

### In-test debugging

```php
// Stop just THIS test, open browser window, pause (implies only())
$page->debug();

// Take a screenshot
$page->screenshot();                          // filename = test name
$page->screenshot(fullPage: true);
$page->screenshot(filename: 'my-custom-name');

// Screenshot a specific element
$page->screenshotElement('#my-chart');

// Open interactive Tinker session in browser context
$page->tinker();

// Wait for manual interaction (opens browser, waits for keypress)
$page->waitForKey();
```

### Global headed mode in Pest.php

```php
pest()->browser()->headed();
```

---

## Laravel Integration

Browser tests work with all of Laravel's testing helpers:

```php
it('completes checkout', function () {
    // Laravel test helpers
    Event::fake();
    Mail::fake();

    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 9999]);

    // Authenticate user before browser session (if needed)
    // $this->actingAs($user); -- works in some setups

    $page = visit('/login');

    $page->fill('email', $user->email)
         ->fill('password', 'password')
         ->press('Sign In')
         ->navigate('/shop')
         ->click($product->name)
         ->click('Add to Cart')
         ->click('Checkout')
         ->fill('card_number', '4242424242424242')
         ->fill('expiry', '12/30')
         ->fill('cvv', '123')
         ->press('Pay Now')
         ->assertSee('Order Confirmed')
         ->assertPathContains('/orders/');

    // Laravel assertions run alongside browser assertions
    $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
    Event::assertDispatched(OrderPlaced::class);
    Mail::assertSent(OrderConfirmation::class);
});
```

### Pest.php setup for Laravel browser tests

```php
// tests/Pest.php
pest()
    ->extend(\Tests\DuskTestCase::class)
    ->use(\Illuminate\Foundation\Testing\DatabaseMigrations::class)
    ->in('Browser');
```

---

## CI/CD — GitHub Actions

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: sockets

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist

      - uses: actions/setup-node@v4
        with:
          node-version: lts/*

      - name: Install NPM dependencies
        run: npm ci

      - name: Install Playwright Browsers
        run: npx playwright install --with-deps

      - name: Run tests
        run: ./vendor/bin/pest --parallel
```

### Test sharding for large suites

```yaml
strategy:
  matrix:
    shard: [1, 2, 3]

steps:
  # ... setup steps ...
  - name: Run tests (shard ${{ matrix.shard }}/3)
    run: ./vendor/bin/pest --shard=${{ matrix.shard }}/3 --parallel
```

---

## Common Patterns

### Authentication flow

```php
it('authenticated users see dashboard', function () {
    $user = User::factory()->create();

    visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->press('Login')
        ->assertPathIs('/dashboard')
        ->assertSee($user->name);
});
```

### Form validation

```php
it('shows validation errors on empty submission', function () {
    $page = visit('/register');

    $page->press('Create Account')
         ->assertSee('The email field is required')
         ->assertSee('The password field is required')
         ->assertPathIs('/register'); // stays on same page
});
```

### Dynamic content / AJAX

```php
it('loads search results dynamically', function () {
    $page = visit('/search');

    $page->fill('query', 'laravel')
         ->press('Search')
         ->wait(1)           // wait for AJAX
         ->assertSee('Results for: laravel')
         ->assertCount('.result-item', 10);
});
```

### Modal interaction

```php
it('can delete via confirmation modal', function () {
    $item = Item::factory()->create();

    $page = visit("/items/{$item->id}");

    $page->click('Delete')
         ->assertVisible('#confirm-modal')
         ->click('#confirm-modal .btn-danger')
         ->assertSee('Item deleted')
         ->assertPathIs('/items');

    $this->assertDatabaseMissing('items', ['id' => $item->id]);
});
```

### Multi-page smoke test

```php
it('has no smoke on public pages', function () {
    $pages = visit(['/', '/about', '/pricing', '/contact', '/login', '/register']);

    $pages->assertNoSmoke()
          ->assertNoAccessibilityIssues();
});
```

### Livewire components

```php
it('filters table via livewire', function () {
    Post::factory(20)->create();
    Post::factory(3)->create(['status' => 'draft']);

    $page = visit('/posts');

    $page->select('status', 'draft')
         ->wait(0.5)  // Livewire re-render
         ->assertCount('table tbody tr', 3);
});
```

---

## Requirements

- PHP >= 8.3
- `ext-sockets` extension required
- Node.js (LTS recommended)
- Playwright installed via npm
- `pestphp/pest` >= 4.3.2
- `pestphp/pest-plugin-browser` >= 4.3.0

---

## Key Concepts Summary

| Concept | Detail |
|---|---|
| Test runner | `./vendor/bin/pest` — no separate runner needed |
| Browser engine | Playwright (Chromium default, Firefox, Safari/WebKit) |
| Selector syntax | Text, CSS, `#id`, `.class`, `@data-test` |
| Default timeout | 5000ms (configure with `pest()->browser()->timeout()`) |
| Screenshots dir | `tests/Browser/Screenshots/` (gitignore this) |
| Laravel integration | Full — RefreshDatabase, Event/Mail fakes, `$this` assertions all work |
| Parallel support | `--parallel` flag — works great with browser tests |
| Sharding | `--shard=N/M` for distributing across CI machines |
| Debug | `--debug` CLI flag or `$page->debug()` in-test |
