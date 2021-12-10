#!/bin/zsh

export PATH=vendor/bin:$PATH

parallel-lint --exclude vendor .
phpcbf src config
phpcs --standard=PSR12 --ignore=vendor src config
phpstan analyse
phpmd src ansi phpmd.xml
phpmd config ansi phpmd.xml
#php-cs-fixer fix src
