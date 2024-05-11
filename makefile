#!make 

setup:
	@composer install
	@cp .env.example .env
	@php artisan key:generate
	@php artisan migrate --force
	@make test
	@make serve

serve: 
	@php artisan serve

test:
	@php artisan test