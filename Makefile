QA_DOCKER_IMAGE=jakzal/phpqa:alpine
QA_DOCKER_COMMAND=docker run -it --rm -v "$(shell pwd):/project" -w /project ${QA_DOCKER_IMAGE}
ELASTICSEARCH_DOCKER_COMPOSE=lib/Elasticsearch/docker-compose.yml

dist: cs-full phpstan test-full
ci: cs-full-check phpstan test-full

test:
	vendor/bin/phpunit --verbose --exclude-group functional,performance

test-full: es-up
	vendor/bin/phpunit --verbose

phpstan:
	sh -c "${QA_DOCKER_COMMAND} phpstan analyse --configuration phpstan.neon --level max ."

cs:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --diff"

cs-full:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff"

cs-full-check:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff --dry-run"

es-up:
	docker-compose -f ${ELASTICSEARCH_DOCKER_COMPOSE} up -d
	# wait for ES to boot
	until curl -s -X GET "http://localhost:9200/" > /dev/null; do sleep 1; done

es-down:
	docker-compose -f ${ELASTICSEARCH_DOCKER_COMPOSE} down
