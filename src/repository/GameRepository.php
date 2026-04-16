<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/Game.php';

// Handles all database operations related to the Game entity
class GameRepository extends Repository {
    // Retrieves all games from the database
    public function getGames(?string $searchString = null): array {
        $baseQuery = '
            SELECT g.id, g.title, g.description, g.category, g.price, g.graphics, g.specification, 
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
                $game['specification']
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
            $game['specification']
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

    // Adds a new game to the database
    public function addGame(Game $game): void {
        $stmt = $this->database->prepare('
            INSERT INTO games (title, description, category, price, graphics, specification)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $game->getTitle(),
            $game->getDescription(),
            $game->getCategory(),
            $game->getPrice(),
            $game->getGraphics(),
            $game->getSpecification()
        ]);
    }

    // Updates an existing game in the database
    public function updateGame(Game $game): void {
        $stmt = $this->database->prepare('
            UPDATE games 
            SET title = ?, description = ?, category = ?, price = ?, graphics = ?, specification = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $game->getTitle(),
            $game->getDescription(),
            $game->getCategory(),
            $game->getPrice(),
            $game->getGraphics(),
            $game->getSpecification(),
            $game->getId()
        ]);
    }

    // Deletes a game from the database
    public function deleteGame(int $id): void {
        $stmt = $this->database->prepare('DELETE FROM games WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}