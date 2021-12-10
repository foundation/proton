# proton

CLI Tool for compiling web assets. This project is in very early stages. Any feedback would be appreciated.

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

## Todo / Ideas

* Dynamically set layout from Front Matter
* PUG support
* Markdown
* Minify HTML
* Layout Rules (config to set default layout for a folder)
* Helpers folder for custom Twig macros?
* debug Option to print out data
* init option to create folders and config file
* Create different pages dynamically based off different data. Maybe `batch` folder?
* AutoIndex Option to output home.html -> home/index.html
* Output option to manually define the name of the published page
* Inky + Inky Extra Twig extensions
