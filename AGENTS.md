# Proton

A PHP CLI static site builder that compiles Twig templates, Markdown, and YAML data into HTML (or any output format). Built on Laravel Zero. Replaces the [panini](https://github.com/foundation/panini) site generator.

## Project structure

- `app/Commands/` — CLI commands (Build, Watch, Init, Data, Help)
- `app/Proton/` — Core classes (Config, Page, Builder, PageWriter, AssetManager, etc.)
- `tests/` — Pest PHP tests (Unit + Feature). Tests create temp fixtures via `TestFixtures` trait — they don't use `sample/`.
- `sample/` — Example site for local development/testing with `proton.yml`
- `builds/proton` — Compiled PHAR binary

## Commands

- **Run tests:** `composer test`
- **Run a single test:** `composer test:filter SomeTest`
- **Build PHAR:** `bin/build.sh [version]` (uses Box to compile, falls back to latest git tag)
- **Run locally:** `php proton <command>`

## Quality tools

All tools are available as composer scripts:

- **PHP-CS-Fixer:** `composer cs` (check) / `composer cs:fix` (fix)
- **PHPStan:** `composer stan` (level 8)
- **PHPMD:** `composer md`
- **PHP_CodeSniffer:** `composer sniffer:check` / `composer sniffer:fix`
- **Rector:** `composer rector` (dry-run) / `composer rector:fix`
- **PHPLint:** `composer lint`
- **Run all checks:** `composer quality`
- **Auto-fix all:** `composer clean`

## Git workflow

Uses **gitflow**. `develop` is the main integration branch. Release branches merge into both `main` and `develop`.

## Code style

- PHP 8.4+
- PSR-12 coding standard
- PSR-4 autoloading (`App\` → `app/`, `Tests\` → `tests/`)
- Pest for testing (not PHPUnit directly)

## Configuration

Projects are configured via `proton.yml` in the project root. Key options: paths, layouts, minify, pretty print, dev server, etc.
