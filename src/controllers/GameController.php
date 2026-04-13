<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/GameRepository.php';
require_once __DIR__ . '/../repository/LibraryRepository.php';

class GameController extends AppController {
    private GameRepository $gameRepository;
    private LibraryRepository $libraryRepository;

    public function __construct() {
        $this->gameRepository = new GameRepository();
        $this->libraryRepository = new LibraryRepository();
    }

    // Displaying the game details page (e.g., /game/1)
    public function show($id) {
        if (!$id) {
            header("Location: /");
            exit();
        }

        $game = $this->gameRepository->getGameById((int)$id);
        if (!$game) {
            return $this->render('404');
        }

        // We check if the user is logged in and if he already owns the game
        $isOwned = false;
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user_id'])) {
            $isOwned = $this->libraryRepository->isGameOwned($_SESSION['user_id'], $game->getId());
        }

        return $this->render("game_details", [
            "title" => $game->getTitle() . " - GameNest",
            "game" => $game,
            "isOwned" => $isOwned
        ]);
    }

    // Endpoint for Fetch API (asynchronous addition to library)
    public function buy() {
        $this->checkAuth(); // Extra security: prevent purchases by unauthenticated users

        if (!$this->isPost()) {
            http_response_code(405); // Method Not Allowed
            return;
        }

        // We receive raw JSON data sent via Fetch API from the browser
        $data = json_decode(file_get_contents('php://input'), true);
        $gameId = $data['gameId'] ?? null;

        if (!$gameId) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Invalid game ID']);
            return;
        }

        $userId = $_SESSION['user_id'];

        // Extra security: does the player already have it?
        if ($this->libraryRepository->isGameOwned($userId, $gameId)) {
            http_response_code(400);
            echo json_encode(['error' => 'You already own this game']);
            return;
        }

        try {
            $this->libraryRepository->addToLibrary($userId, $gameId);
            
            // We return success in JSON format
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Game added to library!']);
        } catch (Exception $e) {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'Server error while processing your request']);
        }
    }
}