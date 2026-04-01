---
raw: true
title: "Template Overview"
description: "Proton's Twig-based template system — layouts, pages, partials, macros, content blocks, and Markdown support."
nav_group: "Core Concepts"
nav_order: 2
---

# Template Overview

Layouts are the highest level of template. These traditionally contain the base HTML for your webpage. This could include the page `<head>` and the basic layouts for your webpages. You can have multiple layouts that can be used across your pages. A page can only have one layout.

Layouts may also contain content blocks. These blocks of content can be overwritten in pages. For example, a layout may have a content block for the header, main content, sidebar and footer. These are on top of the data variables that you can also inject into your templates for further customization.

Partials allow you to create reusable pieces of content that can be used across multiple pages or possible multiple times on that same page. Partials are great for navigation, CTAs, subscription forms and more.

Lastly, macros are basically functions that allow you to pass parameters in order to generate content in your pages. You can use these macros as many times as you want.

**Make sure that you thoroughly review the [Twig for Template Designers](https://twig.symfony.com/doc/3.x/templates.html) documentation.**

## Default Content Block

If no content blocks are defined in your page template, a default `content` block will be added so that you can leverage the content inside of your layouts.

## Markdown

In order to process content as markdown inside of a template, you simple need to make sure that the file extension of the template is `md`. Example: `template.md`

You can also use markdown in parts of your template with a markdown filter.

```
{% markdown %}
### Header

This is my content
{% endmarkdown %}
```

## Syntax Highlighting

Fenced code blocks with a language identifier automatically get syntax highlighting at build time — no client-side JavaScript required. Proton uses [Tempest Highlight](https://github.com/tempestphp/highlight) and supports PHP, JavaScript, HTML, CSS, Twig, YAML, JSON, SQL, Python, Markdown, Dockerfile, and more.

````markdown
```php
$greeting = 'Hello, world!';
echo $greeting;
```
````

The highlighter outputs CSS classes (e.g., `hl-keyword`, `hl-variable`). Include a highlight theme CSS file in your layout to style them. Theme files ship with the package at `vendor/tempest/highlight/src/Themes/Css/` — popular choices include `github-dark.css`, `monokai.css`, and `nord.css`.

Code blocks without a language identifier or with an unrecognized language are rendered as plain escaped text.

## Raw Mode

When your markdown content contains template syntax like `{{ variable }}` or `{% tag %}` (for example, documentation about Twig or other template engines), Proton's Twig processor will try to evaluate those expressions and fail.

Add `raw: true` to the front matter to prevent this. The content will still be processed as markdown, but all `{{ }}` and `{% %}` syntax will be treated as literal text.

```markdown
---
raw: true
title: "My Documentation"
---

# Template Variables

Use `{{ variable }}` to output a value.

Use `{% if condition %}` for conditionals.
```

Raw mode works with both `.md` and `.html` pages:

- **Markdown pages** (`.md`): Content is converted from markdown to HTML, but Twig expressions are preserved as literal text.
- **HTML pages** (`.html`): Block content is wrapped in `{% verbatim %}` so Twig syntax passes through unchanged.

Note: When using `raw: true`, you cannot use Twig variables or includes within the page content. Use front matter for page metadata (like `title`) and access it in your layout with `{{ page.title }}`.

## Heading IDs

All headings in markdown pages automatically get `id` attributes generated from the heading text. For example, `## Getting Started` becomes `<h2 id="getting-started">Getting Started</h2>`. This enables anchor links like `#getting-started`.

IDs are created by lowercasing the text, replacing spaces with hyphens, and stripping non-alphanumeric characters. Headings that start with a number are prefixed with `h-` to ensure valid IDs.

## Table of Contents

Proton provides a `toc()` function that returns the H2 headings extracted from the current page's markdown content. Each entry has `id`, `text`, and `level` properties. This is useful for building on-page navigation:

```twig
{% set page_toc = toc() %}
{% if page_toc|length > 1 %}
<nav>
  <h4>On this page</h4>
  {% for item in page_toc %}
    <a href="#{{ item.id }}">{{ item.text }}</a>
  {% endfor %}
</nav>
{% endif %}
```

The TOC is only populated for markdown pages. HTML pages will return an empty array.

## Asset Helpers

Proton provides helper functions for including CSS and JavaScript files with cache busting and [subresource integrity](https://developer.mozilla.org/en-US/docs/Web/Security/Subresource_Integrity) (SRI) attributes.

### stylesheet

Generates a `<link>` tag with a cache-busting query string and `integrity` attribute.

```twig
{{ stylesheet('/assets/css/app.css') }}
```

```html
<link href="/assets/css/app.css?cache=a1b2c3d4" rel="stylesheet" integrity="sha384-...">
```

### stylesheetAsync

Generates a non-render-blocking `<link>` tag with a `<noscript>` fallback. Useful for non-critical CSS like fonts or below-the-fold styles.

```twig
{{ stylesheetAsync('/assets/css/fonts.css') }}
```

```html
<link rel="stylesheet" href="/assets/css/fonts.css?cache=a1b2c3d4" media="print" onload="this.media='all';" integrity="sha384-...">
<noscript><link rel="stylesheet" href="/assets/css/fonts.css?cache=a1b2c3d4" integrity="sha384-..."></noscript>
```

### script

Generates a `<script>` tag with a cache-busting query string and `integrity` attribute.

```twig
{{ script('/assets/js/site.js') }}
```

```html
<script src="/assets/js/site.js?cache=a1b2c3d4" integrity="sha384-..."></script>
```

### file_hash and file_integrity

For more control, you can use the lower-level functions directly. The `file_hash` function returns a short content hash and `file_integrity` returns an SRI string.

```twig
<script src="/assets/js/site.js?cache={{ file_hash('/assets/js/site.js') }}"></script>

<link href="/assets/css/app.css" rel="stylesheet"
      integrity="{{ file_integrity('/assets/css/app.css') }}">
```

By default, `file_integrity` uses SHA-384. You can pass an alternative algorithm:

```twig
{{ file_integrity('/assets/js/app.js', 'sha256') }}
```

All asset helper paths are resolved relative to the configured `dist` directory. This means both copied assets and files generated by your `npmBuild` command are supported. If a file is not found, cache and integrity attributes are omitted and the tag is output without them.

## Pug

You can process a template using Pug simply by giving the file that `pug` extension. Example: `template.pug`

## Page Destinations

All templates will be named the exact same name in the exact same folder structure inside of the configured `dist` folder. There are the following exceptions:

* All templates with `pug`, `twig` and `md` extensions will become `html` files by default. You can change this with the `defaultExt` configuration value.
* You can customize the path and filename that a page gets output to via the `output` parameter set inside of a page's frontmatter.

## Page Formatting

You can use the `pretty` and `minify` configuration values to determine if the output of a page's HTML will be minified or indented to look "pretty".

## Clear Template Cache

If you are experiencing issues with pages not compiling as you expect, you may need to clear the template cache. You can do this with the `--clear-cache` or `--cc` option when you build.

```sh
$ proton build --clear-cache
```
