# Summit

Summit is an opinionated Laravel Vue starter kit.

## Create a project

Create a fresh application with the Laravel installer:

```bash
laravel new my-project --using=zacksmash/summit
```

The installer creates the environment file and SQLite database, generates the application key and Passport keys, runs the migrations, and launches Chisel. Chisel lets you choose authentication features, teams, Passport, application MCP scaffolding, Octane, browser testing, AI tooling, IDE Helper, Whisky, and Herd integration.

Then start local development:

```bash
cd my-project
composer dev
```

If you pass `--no-node` to `laravel new`, install and build the frontend separately with `npm install && npm run build`.

## Set up a cloned repository

For a direct Git clone instead of a Laravel installer project, run:

```bash
composer setup
```

This performs the same portable application setup without requiring Herd or resetting an existing database.

## Optional local tooling

Install the FrankenPHP runtime for Octane on macOS, Linux, or Windows via WSL:

```bash
composer setup:octane
```

Run the complete opinionated tooling setup, including Octane, Whisky, IDE Helper, Playwright, and Laravel Herd HTTPS proxying:

```bash
composer setup:tools
```

The tooling setup requires Laravel Herd. It initializes a Git repository when needed, installs the Git hooks, and creates an initial commit after all setup and checks succeed. Without an installed Octane runtime, `composer dev` automatically uses Laravel's built-in development server.
