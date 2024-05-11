# Simple Product REST Api

## Setup
1. `git clone git@github.com:Bleckfisk/products-api`
2. `composer install` to install all packages needed
3. `copy .env.example` file and create a `.env` file and put the content in there
4. `php artisan test` to run the provided tests
5. `php artisan serve` to run the server
6. Now use your browser or postman to send requests.


## Routes/Endpoints
All endpoints below are assuming `localhost:8000` unless you've changed something in the configuration files

`/` - Check if we are alive. This will return the laravel version, nothing fancy

`/products` - Get products. Will use page 1 and page size of 5 as default if no query parameters are provided. Supported query parameters are `page_size={int}` and `page={int}`. Example: `/products?page_size=1&page=3`

`/products/{int}` - Get a specific product


