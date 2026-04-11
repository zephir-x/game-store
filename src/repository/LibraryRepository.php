<?php

require_once 'Repository.php';

// Handles fetching games owned by the user using the database View
class LibraryRepository extends Repository {
    // Retrieves the user's library using the predefined PostgreSQL View
    public function getUserLibrary(int $userId): array {
        $stmt = $this->database->prepare('
            SELECT * FROM v_user_library_details 
            WHERE user_id = :userId 
            ORDER BY purchased_at DESC
        ');
        
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}