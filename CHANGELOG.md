# Changelog

All notable changes to Proton are documented in this file.

## [0.10.2] - 2026-03-30

### Fixed
- Asset copying now includes dot files (e.g., `.htaccess`). Previously all dot-prefixed files were skipped.
- Sitemap URLs now use clean paths, stripping `index.html`, `index.htm`, and `index.php` suffixes.

## [0.10.1] - 2026-03-30

### Changed
- Extracted `FrontMatterParser` utility class, consolidating duplicated YAML front matter parsing from `Page`, `PageCollection`, and `PageManager` into a single location.
- Extracted `PageWriter::TEMPLATE_EXTENSIONS` constant, shared with `PageCollection`, eliminating duplicated extension resolution logic.

### Added
- Expanded test suite from 225 to 232 tests covering front matter edge cases, draft filtering variations, watcher change processing, data loading errors, and page writer path building.

## [0.10.0] - 2026-03-30

### Added
- Build-time syntax highlighting for fenced code blocks in Markdown using [Tempest Highlight](https://github.com/tempestphp/highlight). Supports PHP, JavaScript, HTML, CSS, Twig, YAML, JSON, SQL, Python, and more.
- Draft pages: add `draft: true` to front matter to exclude a page from the build output, sitemap, and `proton.pages` in non-development environments.

## [0.9.1] - 2026-03-29

### Fixed
- Build configuration fix.

## [0.9.0] - 2026-03-29

### Added
- Pages collection (`proton.pages`) providing metadata for all pages, enabling dynamic navigation without hardcoding links.
- `toc()` Twig function that returns H2 headings extracted from the current page's Markdown content for on-page navigation.
- New documentation site structure with front matter metadata (`nav_group`, `nav_order`).

## [0.8.0] - 2026-03-20

### Added
- Homebrew tap for installation (`brew install foundation/proton/proton`).
- Auto-update Homebrew tap on release.

### Changed
- Updated installation docs to recommend Homebrew as the default method.

## [0.7.7] - 2026-03-20

### Added
- JSON data file support (`.json`) alongside YAML.
- Configurable dev server port via `port` setting.
- Verbose (`-v`) and quiet (`-q`) CLI output modes for the build command.
- Architecture tests and final Settings DTOs.
- Auto-generated GitHub Release notes in release script.

### Changed
- Moved docs to foundationcss.com/proton.
- New `Config` class with better error handling and dependency injection.
- Code quality improvements: PHPStan fixes, PHP tooling setup, dead code removal.

## [0.7.6] - 2026-03-19

### Added
- Cache busting via `proton.build_time` variable for asset URLs.

## [0.7.5] - 2026-03-19

### Fixed
- Raw mode now correctly preserves the `extends` tag when wrapping content in `verbatim`.

## [0.7.4] - 2026-03-19

### Added
- `raw: true` front matter option for pages containing literal template syntax (e.g., documentation about Twig).

## [0.7.3] - 2026-03-19

### Fixed
- Build process fixes.
