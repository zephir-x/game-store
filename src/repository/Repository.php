<?php

require_once __DIR__ . '/../Database.php';

class Repository {

    // Common PDO connection for all inheriting repositories
    protected PDO $database;

    public function __construct() {
        // Get the instance of the Database singleton and retrieve the active PDO connection
        $this->database = Database::getInstance()->getConnection();
    }
}