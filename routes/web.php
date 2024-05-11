<?php

use App\Http\Controllers\ProductsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/products', [ProductsController::class, 'index']);
Route::get('/products/{id}', [ProductsController::class, 'get']);

Route::any('{any}', function () {
    return ['Unsupported route'];
})->where('any', '^(?!products).*$');

require __DIR__ . '/auth.php';
