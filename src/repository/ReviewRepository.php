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
            SELECT r.id, r.user_id, r.game_id, u.username, ud.avatar, r.rating, r.content, r.created_at
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            LEFT JOIN user_details ud ON u.id = ud.user_id
            WHERE r.game_id = :game_id
            ORDER BY r.created_at DESC
        ');

        // Binding game ID securely to prevent SQL injection
        $stmt->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmt->execute();

        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        
        foreach ($reviews as $review) {
            $result[] = new Review(
                $review['id'],
                $review['user_id'],
                $review['game_id'],
                $review['username'],
                $review['avatar'],
                $review['rating'],
                $review['content'],
                $review['created_at']
            );
        }
        return $result;
    }
}