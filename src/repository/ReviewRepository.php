<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/Review.php';

class ReviewRepository extends Repository {

    public function addReview(Review $review): void {
        // Inserts a new review into the database
        $stmt = $this->database->prepare('
            INSERT INTO reviews (user_id, game_id, rating, content) 
            VALUES (?, ?, ?, ?)
        ');

        // Binding values from Review model to query parameters
        $stmt->execute([
            $review->getUserId(),
            $review->getGameId(),
            $review->getRating(),
            $review->getContent()
        ]);
    }

    public function hasUserReviewed(int $userId, int $gameId): bool {
        // Checks if a specific user has already submitted a review for a given game
        $stmt = $this->database->prepare(
            'SELECT 1 FROM reviews WHERE user_id = :userId AND game_id = :gameId'
        );

        $stmt->execute([
            'userId' => $userId,
            'gameId' => $gameId
        ]);

        // Returns true if any record exists, otherwise false
        return (bool)$stmt->fetch();
    }

    public function getReviewsForGame(int $gameId): array {
        // Retrieves all reviews for a specific game along with the username of the reviewer
        $stmt = $this->database->prepare('
            SELECT r.*, u.username 
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.game_id = :gameId
            ORDER BY r.created_at DESC
        ');

        // Binding game ID securely to prevent SQL injection
        $stmt->bindParam(':gameId', $gameId, PDO::PARAM_INT);
        $stmt->execute();

        $reviews = [];

        // Mapping database rows into Review model objects
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $reviews[] = new Review(
                $row['user_id'],
                $row['game_id'],
                $row['rating'],
                $row['content'],
                $row['id'],
                $row['created_at'],
                $row['username']
            );
        }

        return $reviews;
    }
}