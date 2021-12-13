# proton

CLI Tool for compiling web assets. This project is meant to replace the popular [panini](https://github.com/foundation/panini) site generator.

Managing NPM dependencies has become a difficult task. Both of our development
tools and front end libraries are managed by the same package manager. Too often
we have to deal with dependency conflicts between libraries and tools that we
want to use. Proton helps to combat this problem.

Proton is built with PHP. This means that it's dependencies lay 100% outside of
your project's dependency chain.

Proton leverages [Twig](https://twig.symfony.com) to bring you a powerful, flexible
and fast templating system. Check out the [Twig Templates for Designers](https://twig.symfony.com/doc/3.x/templates.html) docs.

This project is in very early stages. Any feedback would be appreciated.

**PLEASE DO NOT USE THIS FOR PRODUCTION PROJECTS. BREAKING CHANGES MAY BE MADE AT ANY TIME FOR NOW**

## Installation

Proton requires that you have [Composer](https://getcomposer.org) installed. It may be easiest to then install proton globally on your computer. You can do this with the following command.

```sh
composer global require foundation/proton
```

## Build

You can see an example of how to setup proton in the [sample folder](https://github.com/foundation/proton/tree/master/sample). You can also create a [proton.yml](https://github.com/foundation/proton/blob/master/proton.yml) configuration file. Then you can run the following command to build your site.

```sh
proton build
```

## Special Front Matter Variables

* `layout`: defines the layout to use for the page. If no layout is defined the default layout defined in the configuration will be used. You can set this to `none` in order to have no default layout set.
* `output`: This sets the destination name of the page when it gets compiled into the configured `dist` folder.
* `batch`: Batch create pages based on an array of items in your data. This could allow you to create multiple items (such as products) based on the same page but with different data defined within your data.


## Todo / Ideas

* Refactor all code into classes
* PUG support
* Markdown
* Minify HTML
* debug Option to print out data
* init option to create folders and config file
* Inky + Inky Extra Twig extensions
* Check that all config paths exist on load (maybe a verify cli option)
* data cli option to output global data or data for a specific page