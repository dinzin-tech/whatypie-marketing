<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Kernel;
use Core\Http\Request;
use Dotenv\Dotenv;

$request = new Request();

// Load the .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

ini_set('max_execution_time', $_ENV['PHP_MAX_EXECUTION_TIME'] ?? 60);
ini_set('memory_limit', $_ENV['PHP_MEMORY_LIMIT'] ?? '128M');

$kernel = new Kernel();
$kernel->boot();

// Handle request and get response
$response = $kernel->handle($request);

// Send the response
$kernel->terminate($request, $response);