<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/User.php';

// Handles all database operations related to the User entity
class UserRepository extends Repository {
    // Retrieves a user from the database by their email address
    public function getUser(string $email): ?User {
        // Prepare statement to prevent SQL Injection
        $stmt = $this->database->prepare('
            SELECT * FROM users WHERE email = :email
        ');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        // Fetch the result as an associative array
        $user = $stmt->fetch();

        // Return null if user is not found
        if (!$user) {
            return null;
        }

        // Map database array to User object
        return new User(
            $user['email'],
            $user['password_hash'],
            $user['username'],
            $user['role'],
            $user['id']
        );
    }

    // Inserts a new user into the database
    public function addUser(User $user): void {
        $stmt = $this->database->prepare('
            INSERT INTO users (username, email, password_hash, role)
            VALUES (?, ?, ?, ?)
        ');

        // Execute query with bounded parameters
        $stmt->execute([
            $user->getUsername(),
            $user->getEmail(),
            $user->getPassword(),
            $user->getRole()
        ]);
    }
}