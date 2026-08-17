.PHONY: test-unit test-integration phpstan cs

test-unit:
	SKIP_MIGRATIONS=true DB_CONNECTION=sqlite DB_DATABASE=storage/testing.sqlite vendor/bin/phpunit --testsuite Unit

test-integration:
	vendor/bin/phpunit --testsuite Integration

phpstan:
	vendor/bin/phpstan analyse --memory-limit=1G

cs:
	vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
