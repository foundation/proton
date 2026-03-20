---
raw: true
title: "Configuration"
---

# Configuration

At the root of your project you can create a config file called either `proton.yml` or `.proton.yml`. If both exist, `proton.yml` takes precedence.

All settings have sensible defaults — your config file only needs to include the values you want to override.

## Example

```yaml
domain: https://www.mysite.com
autoindex: true
pretty: false
minify: true
sitemap: true
npmBuild: yarn build
layouts:
  default: default.html
  rules:
    blog: blog.html
paths:
  pages: src/pages
  layouts: src/layouts
  partials: src/partials
  dist: dist
```

## Settings Reference

### General

| Setting | Type | Default | Description |
|---|---|---|---|
| `defaultExt` | string | `html` | Output file extension for pages with non-output extensions like `.md`, `.twig`, or `.pug`. |
| `domain` | string | `https://www.example.com` | Your site's domain. Used when generating the sitemap. |
| `autoindex` | bool | `true` | When enabled, non-index pages are wrapped in a subdirectory. For example, `about.html` outputs to `about/index.html` for clean URLs. |
| `debug` | bool | `false` | Enables verbose build output and the Twig `dump()` debug function in templates. |
| `pretty` | bool | `true` | Pretty-prints (indents) the HTML output for readability. |
| `minify` | bool | `false` | Minifies the HTML output. When enabled, this overrides `pretty`. |
| `sitemap` | bool | `true` | Generates a `sitemap.xml` in the dist directory after each build. |
| `npmBuild` | string | `yarn build` | A shell command to run after pages are compiled. Typically used for CSS/JS bundling. Set to an empty string to disable. |
| `devserver` | string | `php` | The dev server used during `proton watch`. |

### Layouts

Control which layout template wraps each page.

| Setting | Type | Default | Description |
|---|---|---|---|
| `layouts.default` | string | `default.html` | The default layout template applied to all pages unless overridden. |
| `layouts.rules` | map | `{}` | Maps a page path prefix to a layout template. For example, `blog: blog.html` applies `blog.html` to any page whose path starts with `blog`. |

A page can override its layout via front matter:

```yaml
---
layout: custom.html
---
```

Set `layout: none` to render a page without any layout.

### Paths

Configure where Proton looks for source files and where it writes output.

| Setting | Type | Default | Description |
|---|---|---|---|
| `paths.dist` | string | `dist` | The build output directory. |
| `paths.assets` | string | `src/assets` | Static assets (images, fonts, etc.) that are copied directly to the dist directory. |
| `paths.data` | string | `src/data` | YAML data files. These are loaded and made available to all templates via the `data` variable. |
| `paths.layouts` | string | `src/layouts` | Twig layout templates that wrap page content. |
| `paths.macros` | string | `src/macros` | Reusable Twig macro files. |
| `paths.pages` | string | `src/pages` | Page templates. Each file here is compiled into an output file. |
| `paths.partials` | string | `src/partials` | Twig partial templates that can be included in pages and layouts. |
| `paths.watch` | string | `src` | The root directory monitored for file changes during `proton watch`. |
