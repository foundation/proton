# Proton

A PHP CLI static site builder that compiles Twig templates, Markdown, and YAML data into HTML (or any output format). Built on Laravel Zero. Replaces the [panini](https://github.com/foundation/panini) site generator.

## Documentation

User-facing docs live in `docs/` (hosted via docs.page). Check there for configuration reference, getting started guides, and feature docs.

## Project structure

- `app/Commands/` — CLI commands (Build, Watch, Init, Data, Help)
- `app/Proton/` — Core classes (Config, Page, Builder, PageWriter, AssetManager, etc.)
- `app/Proton/Settings/` — Typed DTOs for configuration (`Settings`, `Paths`, `Layouts`)
- `tests/` — Pest PHP tests (Unit + Feature). Tests create temp fixtures via `TestFixtures` trait.
- `sample/` — Example site for local development/testing with `proton.yml`
- `builds/proton` — Compiled PHAR binary

## Commands

- **Run tests:** `composer test`
- **Run a single test:** `composer test:filter SomeTest`
- **Build PHAR:** `bin/build.sh [version]` (uses Box to compile, falls back to latest git tag)
- **Run locally:** `php proton <command>`
- **Run all checks:** `composer quality`
- **Auto-fix all:** `composer clean`

## Git workflow

Uses **gitflow**. `develop` is the main integration branch. Release branches merge into both `main` and `develop`.

## Code style

- PHP 8.4+, PSR-12, PSR-4 autoloading (`App\` → `app/`, `Tests\` → `tests/`)
- Pest for testing
- Quality tools: PHP-CS-Fixer, PHPStan (level 8), PHPMD, PHP_CodeSniffer, Rector
