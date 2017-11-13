QA_DOCKER_IMAGE=rollerworks/search-phpqa:latest
QA_DOCKER_COMMAND=docker run -it --rm -v "$(shell pwd):/project" -w /project ${QA_DOCKER_IMAGE}

dist: install cs-full phpstan test-full
ci: cs-full-check phpstan test-full

install:
	composer.phar install --no-progress --no-interaction --no-suggest --optimize-autoloader --ansi

install-dev:
	cp composer.json _composer.json
	composer.phar config minimum-stability dev
	composer.phar update --no-progress --no-interaction --no-suggest --optimize-autoloader --ansi
	mv _composer.json composer.json

install-lowest:
	composer update --no-progress --no-suggest --prefer-stable --prefer-lowest --optimize-autoloader --ansi

test:
	vendor/bin/phpunit --verbose --exclude-group functional,performance

test-full:
	export SYMFONY_DEPRECATIONS_HELPER=strict

	# These tasks should be executed in paralel.
	vendor/bin/phpunit --verbose
	vendor/bin/phpunit --verbose --configuration travis/sqlite.travis.xml
	vendor/bin/phpunit --verbose --configuration travis/pgsql.travis.xml
	vendor/bin/phpunit --verbose --configuration travis/mysql.travis.xml

test-isolated: docker-up
	docker-compose run --rm php make test-full
	@$(MAKE) docker-down

phpstan:
	sh -c "${QA_DOCKER_COMMAND} phpstan analyse --configuration phpstan.neon --level max ."

cs:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --diff"

cs-full:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff"

cs-full-check:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff --dry-run"

docker-up:
	docker-compose up -d
	# wait for ES to boot
	until curl -s -X GET "http://localhost:9200/" > /dev/null; do sleep 1; done

docker-down:
	docker-compose down

.PHONY: install install-dev install-lowest test test-full test-isolated phpstan cs cs-full cs-full-check docker-up down-down
