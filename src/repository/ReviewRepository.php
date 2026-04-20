<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/Review.php';

class ReviewRepository extends Repository {

    // Adds a new review
    public function addReview(int $gameId, int $userId, int $rating, string $content): void {
        $stmt = $this->database->prepare('
            INSERT INTO reviews (game_id, user_id, rating, content) 
            VALUES (:game_id, :user_id, :rating, :content)
        ');
        $stmt->execute([
            'game_id' => $gameId,
            'user_id' => $userId,
            'rating' => $rating,
            'content' => $content
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

    public function getReviewsForGame(int $gameId, ?int $currentUserId = null): array {
        // A query that retrieves a review, counts the likes, and checks whether the logged-in user liked it
        $stmt = $this->database->prepare('
            SELECT r.id, r.user_id, r.game_id, u.username, ud.avatar, r.rating, r.content, r.created_at, r.is_edited,
                   (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.id) as likes_count,
                   (SELECT EXISTS(SELECT 1 FROM review_likes rl2 WHERE rl2.review_id = r.id AND rl2.user_id = :current_user_id)) as is_liked
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            LEFT JOIN user_details ud ON u.id = ud.user_id
            WHERE r.game_id = :game_id
            ORDER BY r.created_at DESC
        ');

        $stmt->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmt->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT); // may be null for guests
        $stmt->execute();

        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        
        foreach ($reviews as $review) {
            $result[] = new Review(
                $review['id'], $review['user_id'], $review['game_id'], $review['username'],
                $review['avatar'], $review['rating'], $review['content'], (bool)$review['is_edited'],
                $review['created_at'],
                (int)$review['likes_count'],
                (bool)$review['is_liked']
            );
        }
        return $result;
    }

    // Gets a single review from a specific user for a given game (needed for editing)
    public function getUserReviewForGame(int $gameId, int $userId) {
        $stmt = $this->database->prepare('
            SELECT * FROM reviews 
            WHERE game_id = :game_id AND user_id = :user_id
        ');
        $stmt->execute(['game_id' => $gameId, 'user_id' => $userId]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $review ? $review : null;
    }

    // Updates an existing review and sets the is_edited flag to true
    public function updateReview(int $gameId, int $userId, int $rating, string $content): void {
        $stmt = $this->database->prepare('
            UPDATE reviews 
            SET rating = :rating, content = :content, is_edited = TRUE 
            WHERE game_id = :game_id AND user_id = :user_id
        ');
        
        $stmt->execute([
            'rating' => $rating,
            'content' => $content,
            'game_id' => $gameId,
            'user_id' => $userId
        ]);
    }

    // Adds or removes a like (returns 'added' or 'removed')
    public function toggleLike(int $userId, int $reviewId): string {
        $stmtCheck = $this->database->prepare('SELECT 1 FROM review_likes WHERE user_id = :uid AND review_id = :rid');
        $stmtCheck->execute(['uid' => $userId, 'rid' => $reviewId]);
        
        if ($stmtCheck->fetch()) {
            $stmtDel = $this->database->prepare('DELETE FROM review_likes WHERE user_id = :uid AND review_id = :rid');
            $stmtDel->execute(['uid' => $userId, 'rid' => $reviewId]);
            return 'removed';
        } else {
            $stmtAdd = $this->database->prepare('INSERT INTO review_likes (user_id, review_id) VALUES (:uid, :rid)');
            $stmtAdd->execute(['uid' => $userId, 'rid' => $reviewId]);
            return 'added';
        }
    }
}