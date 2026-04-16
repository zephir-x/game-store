<?php
session_start();

require_once "Routing.php";

// Get current URL path without query parameters
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

// Try pass cleaned path to router
try {
    Routing::run($path);
} catch (Exception $e) {
    // We catch all unhandled exceptions in the application
    http_response_code(500);
    $title = "500 - Internal Server Error";
    include 'public/views/errors/500.html';
}