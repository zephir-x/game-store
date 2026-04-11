<?php

class Database {
    // A static variable that holds the only instance of this class.
    private static ?Database $instance = null;
    
    // Object of PDO for connection
    private PDO $conn;

    // Private constructor to prevent instantiation
    private function __construct() {
        // Login data consistent with your docker/db/Dockerfile
        $host = 'db'; // name of the service from docker-compose.yaml
        $port = '5432'; // default port within the docker network
        $dbname = 'db';
        $username = 'docker';
        $password = 'docker';

        try {
            // DSN (Data Source Name) for PostgreSQL
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            
            $this->conn = new PDO($dsn, $username, $password);
            
            // Configuration of PDO: Throwing exceptions on errors (important for catching 500)
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Default fetch mode: return results as associative arrays
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            // In case of connection error, throw an appropriate message
            die("Database connection failed: " . $e->getMessage());
        }
    }

    // Method for accessing the Singleton
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        
        return self::$instance;
    }

    // Method for returning the connection object, so we can execute queries
    public function getConnection(): PDO {
        return $this->conn;
    }
}