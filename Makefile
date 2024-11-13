
PHP_BIN ?= php

install: vendor/autoload.php

test: unit lint

unit: vendor/autoload.php
	$(PHP_BIN) vendor/bin/phpunit -c phpunit.xml

coverage: vendor/autoload.php
	rm -rf reports
	$(PHP_BIN) vendor/bin/phpunit -c phpunit.xml --coverage-html reports

lint: vendor/autoload.php
	$(PHP_BIN) vendor/bin/php-cs-fixer check --diff -vvv

fix: vendor/autoload.php
	$(PHP_BIN) vendor/bin/php-cs-fixer fix --diff -vvv

vendor/autoload.php: composer.json composer.lock
	composer install --prefer-dist --no-interaction --ansi --no-progress --no-plugins --no-scripts
	touch vendor/autoload.php

clean:
	rm -rf vendor .php-cs-fixer.cache .phpunit.result.cache
