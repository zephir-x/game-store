<?php
session_start();

// Automatic database initialization
try {
    $dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
    $dbPort = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '5432';
    $dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
    $dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER');
    $dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');

    if ($dbHost && $dbName && $dbUser) {
        $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName}";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // We check if the 'games' table already exists in the database
        $stmt = $pdo->query("SELECT TO_REGCLASS('public.games')");
        $tableExists = $stmt->fetchColumn();

        // If the table does not exist, we execute the init.sql script
        if (!$tableExists) {
            $initSqlPath = __DIR__ . '/../db/init/init.sql';
            if (file_exists($initSqlPath)) {
                $sqlContent = file_get_contents($initSqlPath);
                $pdo->exec($sqlContent);
            }
        }
    }
} catch (Exception $e) {
    // Main application code will handle any connection errors
}
// ----------------------------------------------

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