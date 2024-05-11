# Simple Product REST Api

## Quickstart
The repository contains a make file with some commands. 
To quickstart the application, run `make setup` when standing in the repository.
This covers point 3 to 9 of the setup guide below

### Other makefile commands
`make serve` - start the server

`make test` - run the tests

## Setup
1. `git clone git@github.com:Bleckfisk/products-api`
2. `cd products-api` to go to the project
3. `composer install` to install all packages needed
4. copy content from `.env.example` file and create a `.env` file and put the content in there
5. `php artisan key:generate` to generate a key in the .env file
6. `php artisan migrate` to create the sqlite file required by the system. Answer yes to the question to create the file or use the `--force` flag
7. `php artisan test` to run the provided tests
8. `php artisan serve` to run the server
9. Now use your browser or postman to send requests.

## Routes/Endpoints
All endpoints below are assuming `localhost:8000` unless you've changed something in the configuration files

`/` - Check if we are alive. This will return the laravel version, nothing fancy

`/products` - Get products. Will use page 1 and page size of 5 as default if no query parameters are provided. Supported query parameters are `page_size={int}` and `page={int}`. Example: `/products?page_size=1&page=3`

`/products/{int}` - Get a specific product
