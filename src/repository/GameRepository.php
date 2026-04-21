<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/Game.php';

// Handles all database operations related to the Game entity
class GameRepository extends Repository {
    // Retrieves all games from the database
    public function getGames(?string $searchString = null): array {
        $baseQuery = '
            SELECT g.id, g.title, g.description, g.category, g.price, g.graphics, g.specification, g.developer, g.release_date,
                   v.calculated_rating AS average_rating
            FROM games g
            LEFT JOIN v_game_statistics v ON g.id = v.game_id
        ';

        // If a search string is provided, add a condition to the SQL
        if ($searchString) {
            // ILIKE is a PostgreSQL-specific operator that ignores case
            $baseQuery .= ' WHERE g.title ILIKE :search';
        }

        $baseQuery .= ' ORDER BY g.title ASC';

        $stmt = $this->database->prepare($baseQuery);

        // If there is a phrase, we bind it with the percent symbol (it searches for a string of characters anywhere)
        if ($searchString) {
            $searchParam = '%' . $searchString . '%';
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
        }

        $stmt->execute();

        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];

        foreach ($games as $game) {
            $result[] = new Game(
                $game['id'],
                $game['title'],
                $game['description'],
                $game['category'],
                (float)$game['price'],
                $game['graphics'],
                (float)($game['average_rating'] ?? 0),
                $game['specification'],
                $game['developer'], 
                $game['release_date']
            );
        }

        return $result;
    }

    // Retrieves a single game by its ID.
    public function getGameById(int $id): ?Game {
        $stmt = $this->database->prepare('
            SELECT g.*, v.calculated_rating AS average_rating 
            FROM games g 
            LEFT JOIN v_game_statistics v ON g.id = v.game_id 
            WHERE g.id = :id
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $game = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$game) {
            return null;
        }

        return new Game(
            $game['id'],
            $game['title'],
            $game['description'],
            $game['category'],
            (float)$game['price'],
            $game['graphics'],
            (float)($game['average_rating'] ?? 0),
            $game['specification'],
            $game['developer'],
            $game['release_date']
        );
    }

    // Retrieves game statistics from the v_game_statistics view
    public function getGameStatistics(): array {
        $stmt = $this->database->prepare('
            SELECT game_id, title, price, total_reviews, calculated_rating 
            FROM v_game_statistics 
            ORDER BY total_reviews DESC
        ');

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Retrieves filtered and sorted games from the statistics view
    public function getAllGamesFiltered($filters, $sortColumn, $sortDir) {
        $sql = "SELECT * FROM v_game_statistics WHERE 1=1";
        $params = [];

        // Dynamic filter building
        if (isset($filters['min_id']) && $filters['min_id'] !== '') {
            $sql .= " AND game_id >= :min_id";
            $params[':min_id'] = $filters['min_id'];
        }
        if (isset($filters['max_id']) && $filters['max_id'] !== '') {
            $sql .= " AND game_id <= :max_id";
            $params[':max_id'] = $filters['max_id'];
        }
        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $sql .= " AND price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $sql .= " AND price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        if (isset($filters['min_rating']) && $filters['min_rating'] !== '') {
            $sql .= " AND calculated_rating >= :min_rating";
            $params[':min_rating'] = $filters['min_rating'];
        }
        if (isset($filters['max_rating']) && $filters['max_rating'] !== '') {
            $sql .= " AND calculated_rating <= :max_rating";
            $params[':max_rating'] = $filters['max_rating'];
        }
        if (isset($filters['min_reviews']) && $filters['min_reviews'] !== '') {
            $sql .= " AND total_reviews >= :min_reviews";
            $params[':min_reviews'] = $filters['min_reviews'];
        }
        if (isset($filters['max_reviews']) && $filters['max_reviews'] !== '') {
            $sql .= " AND total_reviews <= :max_reviews";
            $params[':max_reviews'] = $filters['max_reviews'];
        }

        // Whitelist for allowed sorting columns to prevent SQL injection
        $allowedColumns = ['game_id', 'title', 'price', 'calculated_rating', 'total_reviews'];
        if (!in_array($sortColumn, $allowedColumns)) {
            $sortColumn = 'game_id';
        }
        
        $sql .= " ORDER BY " . $sortColumn . " " . $sortDir;

        $stmt = $this->database->prepare($sql);
        $stmt->execute($params); 
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Adds a new game to the database
    public function addGame(Game $game): void {
        $stmt = $this->database->prepare('
            INSERT INTO games (title, description, category, price, graphics, specification, developer, release_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $game->getTitle(),
            $game->getDescription(),
            $game->getCategory(),
            $game->getPrice(),
            $game->getGraphics(),
            $game->getSpecification(),
            $game->getDeveloper(),
            $game->getReleaseDate()
        ]);
    }

    // Updates an existing game in the database
    public function updateGame(Game $game): void {
        $stmt = $this->database->prepare('
            UPDATE games 
            SET title = ?, description = ?, category = ?, price = ?, graphics = ?, specification = ?, developer = ?, release_date = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $game->getTitle(),
            $game->getDescription(),
            $game->getCategory(),
            $game->getPrice(),
            $game->getGraphics(),
            $game->getSpecification(),
            $game->getDeveloper(),
            $game->getReleaseDate(),
            $game->getId()
        ]);
    }

    // Deletes a game from the database
    public function deleteGame(int $id): void {
        $stmt = $this->database->prepare('DELETE FROM games WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Checks if the game is on the user's wishlist
    public function isGameOnWishlist(int $userId, int $gameId): bool {
        $stmt = $this->database->prepare('SELECT 1 FROM wishlist WHERE user_id = :uid AND game_id = :gid');
        $stmt->execute(['uid' => $userId, 'gid' => $gameId]);
        return (bool)$stmt->fetch();
    }

    // Adds or removes a game from the wishlist (returns information about the action performed)
    public function toggleWishlist(int $userId, int $gameId): string {
        if ($this->isGameOnWishlist($userId, $gameId)) {
            $stmt = $this->database->prepare('DELETE FROM wishlist WHERE user_id = :uid AND game_id = :gid');
            $stmt->execute(['uid' => $userId, 'gid' => $gameId]);
            return 'removed';
        } else {
            $stmt = $this->database->prepare('INSERT INTO wishlist (user_id, game_id) VALUES (:uid, :gid)');
            $stmt->execute(['uid' => $userId, 'gid' => $gameId]);
            return 'added';
        }
    }

    // Retrieves all games from the user's wishlist
    public function getUserWishlist(int $userId): array {
        $stmt = $this->database->prepare('
            SELECT w.added_at, g.id AS game_id, g.title, g.category, g.graphics, g.price 
            FROM wishlist w
            JOIN games g ON w.game_id = g.id
            WHERE w.user_id = :uid
            ORDER BY w.added_at DESC
        ');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Checks if the game is on the user's wishlist
    public function isOnWishlist(int $gameId, int $userId): bool {
        $stmt = $this->database->prepare('SELECT 1 FROM wishlist WHERE game_id = :game_id AND user_id = :user_id');
        $stmt->execute(['game_id' => $gameId, 'user_id' => $userId]);
        return (bool)$stmt->fetchColumn();
    }
}