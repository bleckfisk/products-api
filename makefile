#!make 

setup:
	@composer install
	@cp .env.example .env
	@php artisan key:generate
	@php artisan test
	@php artisan serve

serve: 
	@php artisan serve

test:
	@php artisan test