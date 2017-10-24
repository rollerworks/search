dist: test

test: es-up
	vendor/bin/simple-phpunit

es-up:
	docker-compose up -d
	# wait for ES to boot
	until curl -s -X GET "http://localhost:9200/" > /dev/null; do sleep 1; done

es-down:
	docker-compose down
