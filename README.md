# Proton - CLI Tool for compiling web pages

Just like an actual proton is only one part of an atom, Proton is just one piece of the core for your project. It is not intended to be a full fledged CMS. It does zero management of your JavaScript, CSS, Assets or any kind of API that you website may require. Proton can compile your webpage assets into HTML, PHP or whatever language you may require.

This project is meant to replace the popular [panini](https://github.com/foundation/panini) site generator.

```
This project is in very early stages. Any feedback would be appreciated.

PLEASE DO NOT USE THIS FOR PRODUCTION PROJECTS. BREAKING CHANGES MAY BE MADE AT ANY TIME FOR NOW
```

## Proton Features

Here are a list of the main features of Proton and how it can help you.

### Reduce NPM Dependencies

Managing NPM dependencies has become a difficult task. Both of our development tools and front end libraries are managed by the same package manager. Too often we have to deal with dependency conflicts between libraries and tools that we want to use. Proton is built with PHP. This means that it's dependencies lay 100% outside of your project's NPM dependency chain.

### Twig Templates

Proton leverages [Twig](https://twig.symfony.com) to bring you a powerful, flexible and fast templating system. Check out the [Twig Templates for Designers](https://twig.symfony.com/doc/3.x/templates.html) docs. If you like Handlebars, you will be blown away with Twig.

Twig templates can be written in multiple formats. Markdown and Pug will be compiled down to HTML. All other languages, like HTML and PHP, will pass through into the compiled webpages.

You can add front matter to your templates in order to customize options and provide page specific data.

### Template Inheritance

There are 4 levels of templates that you can use: layouts, pages, partials and macros.

Pages are the core template type. You will create pages for each public page that you want on your site (batching aside, see below). Each page can inherit from one layout. Partials and macros allow you to create reusable components that you can use across all of your pages.

### Data Storage

Data can be stored in either YAML or JSON files. You can have multiple data files named differently. All data files are stored in one giant data structure during page compilation. This means that every page has full access to all pages.

Data stored in the YAML frontmatter on each page will override the global data.

### Batch Generate Pages

You can create a single page that can be used to generate multiple pages for each item within a data set. For example, if you can define an array of products inside of your data. You can then create a page for each product by applying these data fro each to the one page template that you created.

## Installation

Proton requires that you have [Composer](https://getcomposer.org) installed. It may be easiest to then install proton globally on your computer. You can do this with the following command.

```sh
composer global require foundation/proton
```

Make sure that you add the composer global installation folder to your shell PATH. By default it should be in the following location: `~/.composer/vendor/bin`

## Getting Started

You can see an example of how to setup proton in the [sample folder](https://github.com/foundation/proton/tree/master/sample). You can also create a [proton.yml](https://github.com/foundation/proton/blob/master/proton.yml) configuration file.

You can use the following command to create the default structure needed for proton. You can optionally add the `--config` option to generate a config file as well.

```sh
$ proton init --config
```

Then you can run the following command to build your site.

```sh
$ proton build
```

## Template Overview

Layouts are the highest level of template. These traditionally contain the base HTML for your webpage. This could include the page `<head>` and the basic layouts for your webpages. You can have multiple layouts that can be used across your pages. A page can only have one layout.

Layouts may also contain content blocks. These blocks of content can be overwritten in pages. For example, a layout may have a content block for the header, main content, sidebar and footer. These are on top of the data variables that you can also inject into your templates for further customization.

Partials allow you to create reusable pieces of content that can be used across multiple pages or possible multiple times on that same page. Partials are great for navigation, CTAs, subscription forms and more.

Lastly, macros are basically functions that allow you to pass parameters in order to generate content in your pages. You can use these macros as many times as you want.

**Make sure that you thoroughly review the [Twig for Template Designers](https://twig.symfony.com/doc/3.x/templates.html) documentation.**

### Default Content Block

If no content blocks are defined in your page template, a default `content` block will be added so that you can leverage the content inside of your layouts.

### Markdown

In order to process content as markdown inside of a template, you simple need to make sure that the file extension of the template is `md`. Example: `template.md`

You can also use markdown in parts of your template with a markdown filter.

```
{% markdown %}
### Header

This is my content
{% endmarkdown %}
```

### Pug

You can process a template using Pug simply by giving the file that `pug` extension. Example: `template.pug`

### Page Destinations

All templates will be named the exact same name in the exact same folder structure inside of the configured `dist` folder. There are the following exceptions:

* All templates with `pug`, `twig` and `md` extensions will become `html` files by default. You can change this with the `defaultExt` configuration value.
* You can customize the path and filename that a page gets output to via the `output` parameter set inside of a page's frontmatter.

### Page Formatting

You can use the `pretty` and `minify` configuration values to determine if the output of a page's HTML will be minified or indented to look "pretty".

## Data Overview

Storing data inside Proton is very flexible to work with Manu different workflows. By default, data is stored in YAML and JSON files inside of the `data` folder. However, this folder is configurable.

### Default data.yml/json

Data stored in the in the `data.yml` or `data.json` files are special in that they are stored at the top level of that global data structure. For example, let's look at this YAML data.

```yaml
project: Proton - CLI Tool for compiling web pages
version: 1.0.0
```

You will be able to insert this data into your page content via standard mustache syntax: `{{ project }}` and `{{ version }}`

### Data Hierarchy

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

### Front Matter Data

You can define data via YAML as frontmatter on any page. Data defined inside of the frontmatter is specific to just that page. Any values defined will overwrite any global data stored.

There are a few special variables that can be defined inside of your frontmatter.

* `layout`: defines the layout to use for the page. If no layout is defined the default layout defined in the configuration will be used. You can set this to `none` in order to have no default layout set.
* `output`: This sets the destination name of the page when it gets compiled into the configured `dist` folder.
* `batch`: Batch create pages based on an array of items in your data. This could allow you to create multiple items (such as products) based on the same page but with different data defined within your data.

Example:

```
---
layout: default.html
title: My awesome webpage
---

My Page Content...
```

### Debugging Data

You can use the `data` command with proton to analyze the data that proton will use to build your pages. This can help you visualize how proton builds the data strucute. You can also pass an optional `--page` parameter in order to take into account a page's front matter so you can see the exact data strcutre used to build a single page.

```sh
$ proton data
$ proton data --page=subfolder/index.html
```

## Configuration

At the root of your project you can create a config file called either `proton.yml` or `.proton.yml`.

```yaml
autoindex: true
debug: false
defaultExt: html
minify: false
pretty: true
paths:
  batch: sample/batch
  data: sample/data
  dist: sample/dist
  layouts: sample/layouts
  macros: sample/macros
  pages: sample/pages
  partials: sample/partials
layouts:
  default: default.html
  rules:
    subfolder: subfolder.html
    blog: blog.html
```

## Todo / Ideas

* Better output logging with debug flag for more output
* Inky + Inky Extra Twig extensions