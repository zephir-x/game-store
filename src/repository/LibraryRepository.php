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

    // Checks if a user already owns a specific game.
    public function isGameOwned(int $userId, int $gameId): bool {
        $stmt = $this->database->prepare('SELECT 1 FROM library WHERE user_id = :userId AND game_id = :gameId');
        $stmt->execute(['userId' => $userId, 'gameId' => $gameId]);
        
        return (bool)$stmt->fetch();
    }

    // Adds a game to the user's library.
    public function addToLibrary(int $userId, int $gameId): void {
        $stmt = $this->database->prepare('
            INSERT INTO library (user_id, game_id) 
            VALUES (:userId, :gameId)
        ');
        $stmt->execute(['userId' => $userId, 'gameId' => $gameId]);
    }
}