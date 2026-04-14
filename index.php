<?php
session_start();

require_once "Routing.php";

// Get current URL path without query parameters
$path = trim($_SERVER["REQUEST_URI"], '/');
$path = parse_url($path, PHP_URL_PATH);

// Try pass cleaned path to router
try {
    Routing::run($path);
} catch (Exception $e) {
    // We catch all unhandled exceptions in the application
    http_response_code(500);
    $title = "500 - Internal Server Error";
    include 'public/views/500.html';
}