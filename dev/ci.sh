#!/bin/sh
set -e
cd /var/www || exit
./vendor/bin/phpstan analyze
./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --verbose -- src tests
./vendor/bin/phpunit --configuration ./phpunit.xml.dist --coverage-clover runtime/.phpunit.cache/coverage.xml
