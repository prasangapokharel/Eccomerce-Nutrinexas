<?php

use App\Core\MiddlewareManager;
use App\Middleware\CartMiddleware;

// Register cart middleware for cart-related routes
MiddlewareManager::register('cart/*', CartMiddleware::class);
MiddlewareManager::register('cart', CartMiddleware::class);

// Register cart middleware for product routes that might add to cart
MiddlewareManager::register('products/*', CartMiddleware::class);
MiddlewareManager::register('products', CartMiddleware::class);

// Register cart middleware for home page (where products are displayed)
MiddlewareManager::register('home', CartMiddleware::class);
MiddlewareManager::register('', CartMiddleware::class);


