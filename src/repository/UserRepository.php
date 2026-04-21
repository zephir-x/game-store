<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/User.php';

// Handles all database operations related to the User entity
class UserRepository extends Repository {
    // Retrieves a user from the database by their email address
    public function getUser(string $email): ?User {
        // Prepare statement to prevent SQL Injection
        $stmt = $this->database->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        // Fetch the result as an associative array
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

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

    // Retrieves user profile details (name, bio, avatar) from user_details table
    public function getUserDetails(int $userId): ?array {
        $stmt = $this->database->prepare('
            SELECT * FROM user_details WHERE user_id = :userId
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $details = $stmt->fetch(PDO::FETCH_ASSOC);

        // If the user has not yet completed their profile, we return null
        if (!$details) {
            return null;
        }

        return $details;
    }

    // Retrieves only the registration date for a specific user
    public function getUserRegistrationDate(int $userId): ?string {
        $stmt = $this->database->prepare('SELECT created_at FROM users WHERE id = :id');
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $date = $stmt->fetchColumn();
        return $date ? $date : null;
    }

    public function getAllUsers(int $currentUserId): array {
        $stmt = $this->database->prepare('
            SELECT id, username, email, role, created_at 
            FROM users 
            WHERE id != :currentId
            ORDER BY created_at DESC
        ');
        $stmt->bindParam(':currentId', $currentUserId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Retrieves a user from the database by their ID
    public function getUserById(int $id): ?User {
        $stmt = $this->database->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        return new User(
            $user['email'],
            $user['password_hash'],
            $user['username'],
            $user['role'],
            $user['id']
        );
    }

    // retrieves filtered and sorted users for the admin panel
    public function getAllUsersFiltered(int $currentUserId, array $filters, string $sortColumn, string $sortDir): array {
        $sql = "SELECT id, username, email, role, created_at FROM users WHERE id != :currentId";
        $params = [':currentId' => $currentUserId];

        // dynamic id filtering
        if (isset($filters['min_id']) && $filters['min_id'] !== '') {
            $sql .= " AND id >= :min_id";
            $params[':min_id'] = $filters['min_id'];
        }
        if (isset($filters['max_id']) && $filters['max_id'] !== '') {
            $sql .= " AND id <= :max_id";
            $params[':max_id'] = $filters['max_id'];
        }

        // date range filtering
        if (isset($filters['min_date']) && $filters['min_date'] !== '') {
            $sql .= " AND DATE(created_at) >= :min_date";
            $params[':min_date'] = $filters['min_date'];
        }
        if (isset($filters['max_date']) && $filters['max_date'] !== '') {
            $sql .= " AND DATE(created_at) <= :max_date";
            $params[':max_date'] = $filters['max_date'];
        }

        // handling role checkboxes
        $validRoles = [];
        if (isset($filters['role_user']) && $filters['role_user'] === 'USER') {
            $validRoles[] = "'USER'";
        }
        if (isset($filters['role_admin']) && $filters['role_admin'] === 'ADMIN') {
            $validRoles[] = "'ADMIN'";
        }

        // apply role restrictions only if explicit filtering is active via the js flag
        if (isset($filters['filtered'])) {
            if (!empty($validRoles)) {
                $sql .= " AND role IN (" . implode(',', $validRoles) . ")";
            } else {
                $sql .= " AND 1=0"; 
            }
        }

        // sorting security and default column assignment
        $allowedColumns = ['id', 'username', 'created_at'];
        if (!in_array($sortColumn, $allowedColumns)) {
            $sortColumn = 'id';
        }

        // force case-insensitive sorting for text columns to prevent ascii value mismatch
        if ($sortColumn === 'username') {
            $sql .= " ORDER BY LOWER(username) " . $sortDir;
        } else {
            $sql .= " ORDER BY " . $sortColumn . " " . $sortDir;
        }

        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Inserts a new user into the database and creates an empty profile using Transactions
    public function addUser(User $user): void {
        try {
            // The database is now "waiting" for final approval
            $this->database->beginTransaction();

            // Add the user to the main table by using RETURNING id - immediately returns the ID of the newly added record
            $stmtUser = $this->database->prepare('
                INSERT INTO users (username, email, password_hash, role)
                VALUES (?, ?, ?, ?) RETURNING id
            ');

            $stmtUser->execute([
                $user->getUsername(),
                $user->getEmail(),
                $user->getPassword(),
                $user->getRole()
            ]);

            // We extract the ID of the created user
            $userId = $stmtUser->fetchColumn();

            // We create a related, empty profile for it in user_details
            $stmtDetails = $this->database->prepare('
                INSERT INTO user_details (user_id) VALUES (?)
            ');
            $stmtDetails->execute([$userId]);

            // If we've reached this point, it means both queries were successful! We're committing the changes to the database
            $this->database->commit();

        } catch (PDOException $e) {
            // We are rolling back all changes from this transaction to maintain 3NF consistency
            $this->database->rollBack();
            
            // We throw an error above so that our SecurityController can display a message in the form
            throw $e; 
        }
    }

    public function updateUserRole(int $id, string $role): void {
        $stmt = $this->database->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute(['role' => $role, 'id' => $id]);
    }

    // Updates everything for a specific user (used only in Admin Panel)
    public function adminUpdateUser(int $userId, array $data): void {
        $this->database->beginTransaction();

        try {
            // Updating master data (username, email)
            $stmtUser = $this->database->prepare('
                UPDATE users SET username = :username, email = :email WHERE id = :id
            ');
            $stmtUser->execute([
                'username' => $data['username'],
                'email' => $data['email'],
                'id' => $userId
            ]);

            // Updating password (only if a new one is provided)
            if (!empty($data['password'])) {
                $hash = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmtPwd = $this->database->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
                $stmtPwd->execute(['hash' => $hash, 'id' => $userId]);
            }

            // Updating profile details
            $stmtDetails = $this->database->prepare('
                UPDATE user_details 
                SET name = :name, surname = :surname, bio = :bio 
                WHERE user_id = :id
            ');
            $stmtDetails->execute([
                'name' => $data['name'] ?? '',
                'surname' => $data['surname'] ?? '',
                'bio' => $data['bio'] ?? '',
                'id' => $userId
            ]);

            $this->database->commit();
        } catch (PDOException $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function updateEmail(int $userId, string $newEmail): void {
        $stmt = $this->database->prepare('UPDATE users SET email = :email WHERE id = :id');
        $stmt->execute(['email' => $newEmail, 'id' => $userId]);
    }

    public function updatePassword(int $userId, string $newHash): void {
        $stmt = $this->database->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $newHash, 'id' => $userId]);
    }

    public function deleteUser(int $id): void {
        $stmt = $this->database->prepare('DELETE FROM users WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}