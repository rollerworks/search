ifndef BUILD_ENV
BUILD_ENV=php7.1
endif

QA_DOCKER_IMAGE=rollerworks/search:latest
QA_DOCKER_COMMAND=docker run --init --interactive --tty --rm --env "COMPOSER_HOME=/composer" --user "$(shell id -u):$(shell id -g)" --volume /tmp/tmp-phpqa-$(shell id -u):/tmp --volume "$(shell pwd):/project" --volume "${HOME}/.composer:/composer" --workdir /project ${QA_DOCKER_IMAGE}

install: composer-install
ci: check test
check: composer-validate cs-check phpstan psalm
lint: check # alias
test: phpunit#-coverage infection

clean:
	rm -rf var/

composer-validate: ensure
	sh -c "${QA_DOCKER_COMMAND} composer validate"
#	sh -c "${QA_DOCKER_COMMAND} composer normalize"

	@for direc in $$(gfind src -mindepth 2 -type f -name composer.json -printf '%h\n'); \
	do \
		sh -c "${QA_DOCKER_COMMAND} composer validate --working-dir=$${direc}"; \
	done;

#	@for direc in $$(gfind src -mindepth 2 -type f -name composer.json -printf '%h\n'); \
#	do \
#		sh -c "${QA_DOCKER_COMMAND} composer validate --working-dir=$${direc}"; \
#		sh -c "${QA_DOCKER_COMMAND} composer normalize --working-dir=$${direc}"; \
#	done;

composer-install: fetch ensure clean
	sh -c "${QA_DOCKER_COMMAND} composer upgrade"

composer-install-lowest: fetch ensure clean
	sh -c "${QA_DOCKER_COMMAND} composer upgrade --prefer-lowest --optimize-autoloader --ansi"

composer-install-dev: fetch ensure clean
	rm -f composer.lock
	cp composer.json _composer.json
	sh -c "${QA_DOCKER_COMMAND} composer config minimum-stability dev"
	sh -c "${QA_DOCKER_COMMAND} composer upgrade --no-progress --no-interaction --no-suggest --optimize-autoloader --ansi"
	mv _composer.json composer.json

cs:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff"

cs-check:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff --dry-run"

phpstan: ensure
	sh -c "${QA_DOCKER_COMMAND} phpstan analyse"

psalm: ensure
	sh -c "${QA_DOCKER_COMMAND} php vendor/bin/psalm --show-info=false"

infection: phpunit-coverage
	docker-compose run --rm php phpdbg -qrr /tools/infection run --verbose --show-mutations --no-interaction --only-covered --coverage var/ --min-msi=84 --min-covered-msi=84

phpunit-coverage: ensure docker-up
	docker-compose run --rm php phpdbg -qrr vendor/bin/phpunit --verbose --coverage-text --log-junit=var/phpunit.junit.xml --coverage-xml var/coverage-xml/

# Cannot be done yet
#	sh -c "${QA_DOCKER_COMMAND} phpdbg -qrr vendor/bin/phpunit --verbose --configuration travis/sqlite.travis.xml --coverage-text --log-junit=var/phpunit-sqlite.junit.xml --coverage-xml var/coverage-xml-sqlite/
#	sh -c "${QA_DOCKER_COMMAND} phpdbg -qrr vendor/bin/phpunit --verbose --configuration travis/pgsql.travis.xml --coverage-text --log-junit=var/phpunit-pgsql.junit.xml --coverage-xml var/coverage-xml-pgsql/"
#	sh -c "${QA_DOCKER_COMMAND} phpdbg -qrr vendor/bin/phpunit --verbose --configuration travis/mysql.travis.xml --coverage-text --log-junit=var/phpunit-mysql.junit.xml --coverage-xml var/coverage-xml-mysql/"

phpunit: docker-up
	docker-compose run --rm php php vendor/bin/phpunit --verbose
	docker-compose run --rm php php vendor/bin/phpunit --verbose --configuration travis/sqlite.travis.xml
	docker-compose run --rm php php vendor/bin/phpunit --verbose --configuration travis/pgsql.travis.xml
	docker-compose run --rm php php vendor/bin/phpunit --verbose --configuration travis/mysql.travis.xml

ensure:
	mkdir -p ${HOME}/.composer /tmp/tmp-phpqa-$(shell id -u)

fetch:
	docker pull "${QA_DOCKER_IMAGE}"

docker-up: ensure
	docker-compose up -d
	# wait for ES to boot
	until curl -s -X GET "http://localhost:59200/" > /dev/null; do sleep 1; done

docker-down:
	docker-compose down
