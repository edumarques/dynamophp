# Executables (host)
DOCKER_COMPOSE = docker compose

# Docker containers
APP_CONTAINER = $(DOCKER_COMPOSE) exec app

# Executables
PHP      = $(APP_CONTAINER) php
COMPOSER = $(APP_CONTAINER) composer

# Misc
.DEFAULT_GOAL = help

## 👷 Makefile
help: ## Outputs this help screen
	@grep '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed 's/\[32m##/[33m/'

## 🐳 Docker
build: ## Builds the Docker images
	@$(DOCKER_COMPOSE) --progress=plain build --pull --no-cache

up: ## Start the Docker cluster
	@$(DOCKER_COMPOSE) up

start: ## Start the Docker cluster in detached mode (no logs)
	@$(DOCKER_COMPOSE) up --detach

stop: ## Stop the Docker cluster
	@$(DOCKER_COMPOSE) stop

down: ## Stop and remove the Docker cluster
	@$(DOCKER_COMPOSE) down --remove-orphans --volumes

logs: ## Show live logs
	@$(DOCKER_COMPOSE) logs --tail=0 --follow

ps: ## Show containers' statuses
	@$(DOCKER_COMPOSE) ps

sh: ## Connect to the app container
	@$(APP_CONTAINER) sh

php: ## Run PHP on app container, pass the parameter "args=" to append arguments (example: make php args='script.php')
	@$(PHP) ${args}

## ✅ Code Quality
hooks: ## Enable Git hooks
	git config --local core.hooksPath .hooks/

phpcs: ## Run PHP Code Sniffer
	@$(APP_CONTAINER) vendor/bin/phpcs

phpcs-fix: ## Run PHP Code Sniffer (fix)
	@$(APP_CONTAINER) vendor/bin/phpcbf

phpstan: ## Run PHPStan
	@$(APP_CONTAINER) vendor/bin/phpstan

lint: phpcs phpstan ## Run PHP Code Sniffer and PHPStan

test-unit: ## Run unit tests, pass the parameter "args=" to append arguments (example: make test-unit args='--filter=file.php')
	@$(DOCKER_COMPOSE) exec app vendor/bin/phpunit --testsuite=unit --testdox ${args}

test-integration: ## Run integration tests, pass the parameter "args=" to append arguments (example: make test-integration args='--filter=file.php')
	@$(DOCKER_COMPOSE) exec app vendor/bin/phpunit --testsuite=integration --testdox ${args}

test: ## Run all tests, pass the parameter "args=" to append arguments (example: make test args='--filter=file.php')
	@$(DOCKER_COMPOSE) exec app vendor/bin/phpunit --testdox ${args}

test-cov-unit: ## Run unit tests and generate coverage report
	@$(DOCKER_COMPOSE) exec -e XDEBUG_MODE=coverage app vendor/bin/phpunit --testsuite=unit --testdox --coverage-clover coverage/clover/clover.xml --coverage-html coverage/html

test-cov-integration: ## Run integration tests and generate coverage report
	@$(DOCKER_COMPOSE) exec -e XDEBUG_MODE=coverage app vendor/bin/phpunit --testsuite=integration --testdox --coverage-clover coverage/clover/clover.xml --coverage-html coverage/html

test-cov: ## Run all tests and generate coverage report
	@$(DOCKER_COMPOSE) exec -e XDEBUG_MODE=coverage app vendor/bin/phpunit --testdox --coverage-clover coverage/clover/clover.xml --coverage-html coverage/html --log-junit coverage/junit.xml

cov-unit: test-cov-unit cov-report ## Generate and open unit test coverage report

cov-integration: test-cov-integration cov-report ## Generate and open integration test coverage report

cov: test-cov cov-report ## Generate and open test coverage report

cov-report: ## Open test coverage report
	open coverage/html/index.html

## 🧙 Composer
composer: ## Run Composer, pass the parameter "c=" to run a given command (example: make composer c='req vendor/package')
	@$(COMPOSER) $(c)
