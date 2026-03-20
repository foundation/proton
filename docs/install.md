---
raw: true
title: "Installation"
---

# Installation

## Homebrew (recommended)

The easiest way to install Proton on macOS or Linux is via Homebrew.

```sh
brew tap foundation/proton
brew install proton
```

To upgrade to the latest version:

```sh
brew upgrade proton
```

## Download the PHAR

You can download the latest `proton` PHAR binary directly from the [GitHub Releases](https://github.com/foundation/proton/releases) page. Place it somewhere in your `PATH` and make it executable:

```sh
chmod +x proton
mv proton /usr/local/bin/
```

## Composer

You can also install Proton globally via [Composer](https://getcomposer.org):

```sh
composer global require foundation/proton
```

Make sure the Composer global bin directory is in your `PATH`. By default this is `~/.composer/vendor/bin`.
