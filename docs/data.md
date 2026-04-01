---
raw: true
title: "Data Overview"
description: "Store and access data in Proton using YAML and JSON files. Includes front matter, global data, the pages collection, and batch pages."
nav_group: "Core Concepts"
nav_order: 3
---

# Data Overview

Storing data inside Proton is very flexible to work with many different workflows. By default, data is stored in YAML and JSON files inside of the `data` folder. Proton supports `.yml`, `.yaml`, and `.json` extensions — any other file types in the data directory are ignored. The data folder is configurable via `paths.data` in your config.

## Default data.yml/json

Data stored in the in the `data.yml` or `data.json` files are special in that they are stored at the top level of that global data structure. For example, let's look at this YAML data.

```yaml
project: Proton - CLI Tool for compiling web pages
version: 1.0.0
```

You will be able to insert this data into your page content via standard mustache syntax: `{{ project }}` and `{{ version }}`

## Data Hierarchy

There are many ways to create hierarchy within your data.

Inside of your default data file (see above), you can add your own hierarchy inside of the yaml/json.

```yaml
level1:
	level2A:
		propA: lorem ipsum
		propB: lorem ipsum
	level2B:
		propA: lorem ipsum
		propB: lorem ipsum
```

The above data would be the same as if you were to create a file named `level1.yml` with the following content.

```yaml
level2A:
	propA: lorem ipsum
	propB: lorem ipsum
level2B:
	propA: lorem ipsum
	propB: lorem ipsum
```

The last way would be to add a folder structure into the mix. If you create a folder inside your data folder named `level1`, then crate yaml files for each level2 object: `level1/level2A.yml` and `level1/level2B.yml`. Each of these files would contain their data.

```yaml
propA: lorem ipsum
propB: lorem ipsum
```

For all of the example above you can access the data just like you would traditionally with mustache templates: `{{ level1.level2A.propB }}`

Twig provides many logic based functions like `for` loops that allow you iterate through data to create content dynamically.

## Page Variables

Every page has access to a `page` object containing metadata about the current page. These variables are available in both page templates and layouts via `{{ page.* }}`.

| Variable | Type | Description |
|---|---|---|
| `page.url` | string | The page's URL path (e.g., `/about/` or `/docs/install/`) |
| `page.canonical` | string | Full canonical URL including the configured domain (e.g., `https://example.com/about/`) |
| `page.title` | string | The page title. Falls back to a title generated from the filename (e.g., `getting-started` becomes "Getting started") |
| `page.filename` | string | The source filename without extension (e.g., `about`, `index`) |
| `page.ext` | string | The source file extension (e.g., `html`, `md`) |
| `page.dirname` | string\|null | The directory relative to the pages folder, or `null` for root-level pages (e.g., `docs`, `blog/news`) |
| `page.path` | string | The full source path relative to the pages folder (e.g., `blog/post.html`) |
| `page.outputPath` | string | The resolved output file path relative to the dist folder (e.g., `about/index.html`) |
| `page.depth` | int | Directory nesting level. `0` for root pages, `1` for `blog/post.html`, `2` for `blog/news/item.html`, etc. |
| `page.isIndex` | bool | Whether the page is an index page (filename is exactly `index`) |
| `page.parent` | string | The parent directory URL (e.g., `/blog/` for `blog/post.html`, `/` for root pages) |
| `page.slug` | string | URL-safe normalized filename (lowercased, non-alphanumeric characters replaced with hyphens) |

Any front matter field you define is also available on the `page` object. For example, `author: Joe` in front matter is accessible as `{{ page.author }}`.

The `url`, `title`, `canonical`, and `slug` fields can be overridden via front matter. Structural fields like `filename`, `dirname`, `ext`, and `path` are always derived from the source file.

## Pages Collection

Proton automatically collects metadata about all pages and makes it available as `proton.pages`. Each entry includes all of the page variables listed above, plus any custom front matter fields. This enables dynamic navigation without hardcoding links.

```twig
{% for p in proton.pages|filter(p => p.dirname == 'docs') %}
  <a href="{{ p.url }}">{{ p.title }}</a>
{% endfor %}
```

You can add custom front matter fields like `nav_group` and `nav_order` to organize pages into groups:

```yaml
---
title: "Configuration"
nav_group: "Core Concepts"
nav_order: 1
---
```

Then build grouped navigation in your layout:

```twig
{% set nav_groups = ["Getting Started", "Core Concepts", "Reference"] %}
{% set nav_pages = proton.pages|filter(p => p.dirname == 'docs' and p.nav_group is defined)|sort((a, b) => a.nav_order <=> b.nav_order) %}
{% for group in nav_groups %}
<div>
  <h3>{{ group }}</h3>
  {% for p in nav_pages|filter(p => p.nav_group == group) %}
    <a href="{{ p.url }}">{{ p.title }}</a>
  {% endfor %}
</div>
{% endfor %}
```

Here are a few more examples using the page variables:

```twig
{# Breadcrumb navigation using depth and parent #}
{% if page.depth > 0 %}
  <a href="{{ page.parent }}">Back</a> / {{ page.title }}
{% endif %}

{# Canonical link tag in your layout head #}
<link rel="canonical" href="{{ page.canonical }}">

{# Active nav highlighting using path #}
<a href="/about/" {% if page.path == 'about.html' %}class="active"{% endif %}>About</a>

{# Filter collection to only index pages #}
{% for p in proton.pages|filter(p => p.isIndex) %}
  <a href="{{ p.url }}">{{ p.title }}</a>
{% endfor %}
```

Pages without a `title` in their front matter will use a title generated from the filename (e.g., `getting-started.md` becomes "Getting started").

## Front Matter Data

You can define data via YAML as frontmatter on any page. Data defined inside of the frontmatter is specific to just that page. Any values defined will overwrite any global data stored.

There are a few special variables that can be defined inside of your frontmatter.

* `layout`: defines the layout to use for the page. If no layout is defined the default layout defined in the configuration will be used. You can set this to `none` in order to have no default layout set.
* `output`: This sets the destination name of the page when it gets compiled into the configured `dist` folder.
* `batch`: Batch create pages based on an array of items in your data. This could allow you to create multiple items (such as products) based on the same page but with different data defined within your data.
* `draft`: When set to `true`, the page is excluded from the build output, the sitemap, and `proton.pages` — unless `PROTON_ENV` is set to `development`. This lets you work on pages locally without publishing them.

Example:

```yaml
---
layout: default.html
title: My awesome webpage
---

My Page Content...
```

## Draft Pages

Mark a page as a draft by adding `draft: true` to its front matter:

```yaml
---
title: "Work in Progress"
draft: true
---
```

Draft pages are **included** when `PROTON_ENV` is `development` (the default) and **excluded** from the build output, sitemap, and `proton.pages` in all other environments. This means drafts are visible during local development but hidden in production builds.

To build without drafts locally, set the environment variable before running the build:

```sh
PROTON_ENV=production proton build
```

## Debugging Data

You can use the `data` command with proton to analyze the data that proton will use to build your pages. This can help you visualize how proton builds the data strucute. You can also pass an optional `--page` parameter in order to take into account a page's front matter so you can see the exact data strcutre used to build a single page.

```sh
$ proton data
$ proton data --page=subfolder/index.html
```

## Tips

* You cannot use a data variable on a page that has a page content block that uses the same name. For example, if you had a variable `{{ title }}` on a page, you cannot also use the content block `{% block title %}{% endblock %}` since both use the ID of `title`.
