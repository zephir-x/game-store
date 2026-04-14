<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/Game.php';

// Handles all database operations related to the Game entity
class GameRepository extends Repository {
    // Retrieves all games from the database
    public function getGames(): array {
        $result = [];
        
        $stmt = $this->database->prepare('SELECT * FROM games ORDER BY created_at DESC');
        $stmt->execute();
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($games as $game) {
            $result[] = new Game(
                $game['id'],
                $game['title'],
                $game['description'],
                $game['category'],
                (float)$game['price'],
                $game['graphics'],
                (float)$game['average_rating'],
                $game['specification']
            );
        }

        return $result;
    }

    // Retrieves a single game by its ID.
    public function getGameById(int $id): ?Game {
        $stmt = $this->database->prepare('SELECT * FROM games WHERE id = :id');
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
            (float)$game['average_rating'],
            $game['specification']
        );
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