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

Layouts are the highest level of template. These traditionally contain the base HTML for your webpage. This could include the page `<head>` and the basic layouts for your webpages. You can have multiple layouts that can be used across your pages. A page can only have one layout.

Layouts may also contain content blocks. These blocks of content can be overwritten in pages. For example, a layout may have a content block for the header, main content, sidebar and footer. These are on top of the data variables that you can also inject into your templates for further customization. 

Partials allow you to create reusable pieces of content that can be used across multiple pages or possible multiple times on that same page. Partials are great for navigation, CTAs, subscription forms and more. 

Lastly, macros are basically functions that allow you to pass parameters in order to generate content in your pages. You can use these macros as many times as you want. 

### Data Storage

Data can be stored in either YAML or JSON files. You can have multiple data files named differently. All data files are stored in one giant data structure during page compilation. This means that every page has full access to all pages. 

Data stored in the YAML frontmatter on each page will override the global data. 

#### Default data.yml/json

Data stored in the in the `data.yml` or `data.json` files are special in that they are stored at the top level of that global data structure. For example, let's look at this YAML data. 

```yaml
project: Proton - CLI Tool for compiling web pages
version: 1.0.0 
```

You will be able to insert this data into your page content via standard mustache syntax: `{{ project }}` and `{{ version }}`

#### Data Hierarchy 

There are many ways to create hierarchy within your data. 

Inside of your default data file (see above), you an add your own hierarchy inside of the yaml/json. 

```yaml

```

### Batch Generate Pages

## Installation

Proton requires that you have [Composer](https://getcomposer.org) installed. It may be easiest to then install proton globally on your computer. You can do this with the following command.

```sh
composer global require foundation/proton
```

Make sure that you add the composer global installation folder to your shell PATH. By default it should be in the following location: `~/.composer/vendor/bin`

## Build

You can see an example of how to setup proton in the [sample folder](https://github.com/foundation/proton/tree/master/sample). You can also create a [proton.yml](https://github.com/foundation/proton/blob/master/proton.yml) configuration file. Then you can run the following command to build your site.

```sh
proton build
```

## Special Front Matter Variables

* `layout`: defines the layout to use for the page. If no layout is defined the default layout defined in the configuration will be used. You can set this to `none` in order to have no default layout set.
* `output`: This sets the destination name of the page when it gets compiled into the configured `dist` folder.
* `batch`: Batch create pages based on an array of items in your data. This could allow you to create multiple items (such as products) based on the same page but with different data defined within your data.


## Default Content Block

If no content blocks are defined in your page template, a default `content` block will be added so that you can leverage the content inside of your layouts.

## Todo / Ideas

* Refactor all code into classes
* Better output logging with debug flag for more output
* init option to create folders and config file
* Inky + Inky Extra Twig extensions
* Check that all config paths exist on load (maybe a verify cli option)
* data cli option to output global data or data for a specific page