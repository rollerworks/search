QA_DOCKER_IMAGE=rollerworks/search-phpqa:latest
QA_DOCKER_COMMAND=docker run -it --rm -v "$(shell pwd):/project" -w /project ${QA_DOCKER_IMAGE}

dist: install cs-full phpstan test
ci: cs-full-check phpstan test
lint: cs-full-check phpstan

install:
	docker-compose run --rm php make in-docker-install

install-dev:
	docker-compose run --rm php make in-docker-install-dev

install-lowest:
	docker-compose run --rm php make in-docker-install-lowest

test: docker-up
	docker-compose run --rm php make in-docker-test
	@$(MAKE) docker-down

test-coverage: docker-up
	mkdir -p build/logs build/cov
	docker-compose run --rm php make in-docker-test-coverage
	sh -c "${QA_DOCKER_COMMAND} phpdbg -qrr /usr/local/bin/phpcov merge --clover build/logs/clover.xml build/cov"
	@$(MAKE) docker-down

phpstan:
	sh -c "${QA_DOCKER_COMMAND} phpstan analyse --configuration phpstan.neon --level 5 ."

cs:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --diff"

cs-full:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff"

cs-full-check:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff --dry-run"

##
# Special operations
##

docker-up:
	docker-compose up -d
	# wait for ES to boot
	until curl -s -X GET "http://localhost:59200/" > /dev/null; do sleep 1; done

docker-down:
	docker-compose down

##
# Private targets
##
in-docker-install:
	rm -f composer.lock
	composer.phar install --no-progress --no-interaction --no-suggest --optimize-autoloader --ansi

in-docker-install-dev:
	rm -f composer.lock
	cp composer.json _composer.json
	composer.phar config minimum-stability dev
	composer.phar update --no-progress --no-interaction --no-suggest --optimize-autoloader --ansi
	mv _composer.json composer.json

in-docker-install-lowest:
	rm -f composer.lock
	composer update --no-progress --no-suggest --prefer-stable --prefer-lowest --optimize-autoloader --ansi

in-docker-test:
	SYMFONY_DEPRECATIONS_HELPER=weak vendor/bin/phpunit --verbose
	SYMFONY_DEPRECATIONS_HELPER=weak vendor/bin/phpunit --verbose --configuration travis/sqlite.travis.xml
	SYMFONY_DEPRECATIONS_HELPER=weak vendor/bin/phpunit --verbose --configuration travis/pgsql.travis.xml
	SYMFONY_DEPRECATIONS_HELPER=weak vendor/bin/phpunit --verbose --configuration travis/mysql.travis.xml

in-docker-test-coverage:
	SYMFONY_DEPRECATIONS_HELPER=weak phpdbg -qrr vendor/bin/phpunit --verbose --coverage-php build/cov/coverage-phpunit.cov
	SYMFONY_DEPRECATIONS_HELPER=weak phpdbg -qrr vendor/bin/phpunit --verbose --configuration travis/sqlite.travis.xml --coverage-php build/cov/coverage-phpunit-sqlite.cov
	SYMFONY_DEPRECATIONS_HELPER=weak phpdbg -qrr vendor/bin/phpunit --verbose --configuration travis/pgsql.travis.xml --coverage-php build/cov/coverage-phpunit-pgsql.cov
	SYMFONY_DEPRECATIONS_HELPER=weak phpdbg -qrr vendor/bin/phpunit --verbose --configuration travis/mysql.travis.xml --coverage-php build/cov/coverage-phpunit-mysql.cov

.PHONY: install install-dev install-lowest phpstan cs cs-full cs-full-checks docker-up down-down
.PHONY: in-docker-install in-docker-install-dev in-docker-install-lowest in-docker-test in-docker-test-coverage
