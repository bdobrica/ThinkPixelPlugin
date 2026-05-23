COMPOSE_FILE := docker/unit-tests/docker-compose.yml
COMPOSE := docker compose -f $(COMPOSE_FILE)
COMPOSER_IMAGE := composer:2
PHPUNIT_CONFIG := /opt/tests/phpunit.xml
TEST_SERVICE := wordpress-test

.PHONY: test-deps test-build test-db-up test test-notices test-shell test-down

test-deps:
	docker run --rm -v "$(CURDIR)/tests:/app" -w /app $(COMPOSER_IMAGE) composer install --no-interaction --prefer-dist

test-build:
	$(COMPOSE) build $(TEST_SERVICE)

test-db-up:
	$(COMPOSE) up -d mariadb
	until $(COMPOSE) exec -T mariadb sh -lc 'mariadb-admin ping -h127.0.0.1 -u"$$MYSQL_USER" -p"$$MYSQL_PASSWORD" --silent' >/dev/null 2>&1; do \
		sleep 1; \
	done

test: test-deps test-build test-db-up
	$(COMPOSE) run --rm $(TEST_SERVICE) phpunit --configuration $(PHPUNIT_CONFIG)

test-notices: test-deps test-build test-db-up
	$(COMPOSE) run --rm $(TEST_SERVICE) phpunit --configuration $(PHPUNIT_CONFIG) --display-phpunit-notices

test-shell: test-deps test-build test-db-up
	$(COMPOSE) run --rm $(TEST_SERVICE) bash

test-down:
	$(COMPOSE) down -v
