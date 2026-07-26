# BrewMo Makefile
# Provides common development commands

.PHONY: help install test lint analyse serve

# Default target
help:
	@echo "BrewMo Development Commands:"
	@echo ""
	@echo "  make install    - Install dependencies with Composer"
	@echo "  make test       - Run PHPUnit tests"
	@echo "  make lint      - Run PHPStan static analysis"
	@echo "  make analyse    - Run Psalm static analysis"
	@echo "  make fix       - Fix code style with PHP-CS-Fixer"
	@echo "  make serve     - Start PHP development server"
	@echo "  make docker-up - Start Docker containers"
	@echo "  make docker-down - Stop Docker containers"
	@echo ""

# Install dependencies
install:
	composer install --no-progress --prefer-dist

# Run tests
test:
	vendor/bin/phpunit

# Run PHPStan static analysis
lint:
	vendor/bin/phpstan analyse src/ --level=5

# Run Psalm static analysis
analyse:
	vendor/bin/psalm src/

# Fix code style
fix:
	php-cs-fixer fix src/ tests/ --rules=@PSR12

# Start PHP development server
serve:
	php -S localhost:8000 -t www/

# Docker commands
docker-up:
	docker-compose up -d

docker-down:
	docker-compose down

docker-logs:
	docker-compose logs -f

docker-ps:
	docker-compose ps

# Database migration
db-migrate:
	@echo "Running database migrations..."
	@# In a real implementation, this would run migration scripts

# Clean up
clean:
	rm -rf vendor/
	rm -rf docker/sessions/*
	rm -rf docker/php-error.log
