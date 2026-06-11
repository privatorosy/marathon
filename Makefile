DEFAULT_GOAL := help

.PHONY: help install install-opti cache-clear cache clear cache-warmup warmup test tests phpunit compile build build-prod build-without-tests build-without-test compile-without-tests compile-without-test

help: ## Display available commands
	@echo "Marathon - Available commands"
	@awk 'BEGIN {FS = ":.*##"} \
		/^##[^#]/ {gsub(/^##[[:space:]]*/, "", $$0); if (length($$0) > 0) printf "\n%s\n", $$0} \
		/^[a-zA-Z0-9_.-]+:.*##/ {printf "  %-28s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

##
##Install
##
install-opti: ## Composer install optimized
	composer install --no-scripts --no-autoloader --no-dev
	composer bin box require humbug/box
	composer dump-autoload --classmap-authoritative --no-dev --optimize
install: ## Composer install
	composer install --no-scripts --no-autoloader
	composer bin box require humbug/box
	composer dump-autoload --classmap-authoritative --optimize

##
##Cache
##
cache-clear: ## Cache clear
	php bin/console cache:clear
cache: cache-clear
clear: cache-clear
cache-warmup: ## Cache warmup
	php bin/console cache:warmup
warmup: cache-warmup

##
##Tests
##
test: install ## Run PHPUnit tests
	chmod +x bin/phpunit
	php bin/phpunit
tests: test ## Run PHPUnit tests
phpunit: test ## Run PHPUnit tests

##
##Build
##
compile: tests install-opti cache warmup ## Build Marathon project
	./vendor-bin/box/vendor/humbug/box/bin/box compile
build: compile ## Build Marathon project

build-prod: tests install-opti cache ## Build Marathon project
	./vendor-bin/box/vendor/humbug/box/bin/box compile

build-without-tests: install-opti cache ## Build Marathon project without running tests
	./vendor-bin/box/vendor/humbug/box/bin/box compile
build-without-test: build-without-tests ## Build Marathon project without running tests
compile-without-tests: build-without-tests ## Build Marathon project without running tests
compile-without-test: build-without-tests ## Build Marathon project without running tests
