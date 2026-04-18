<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/GameRepository.php';
require_once __DIR__ . '/../repository/LibraryRepository.php';
require_once __DIR__ . '/../repository/ReviewRepository.php';

class GameController extends AppController {
    private GameRepository $gameRepository;
    private LibraryRepository $libraryRepository;
    private ReviewRepository $reviewRepository;

    public function __construct() {
        $this->gameRepository = new GameRepository();
        $this->libraryRepository = new LibraryRepository();
        $this->reviewRepository = new ReviewRepository();
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
        $hasReviewed = false;
        $isOnWishlist = false;

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $isOwned = $this->libraryRepository->isGameOwned($userId, $game->getId());
            $hasReviewed = $this->reviewRepository->hasUserReviewed($userId, $game->getId());
            $isOnWishlist = $this->gameRepository->isGameOnWishlist($userId, $game->getId());
        }

        $reviews = $this->reviewRepository->getReviewsForGame($game->getId());

        return $this->render("game_details", [
            "title" => $game->getTitle() . " - GameNest",
            "game" => $game,
            "isOwned" => $isOwned,
            "hasReviewed" => $hasReviewed,
            "isOnWishlist" => $isOnWishlist,
            "reviews" => $reviews
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

    // Classic review submission form
    public function addReview() {
        $this->checkAuth();

        if (!$this->isPost()) {
            header("Location: /");
            exit();
        }

        $gameId = (int)$_POST['game_id'];
        $rating = (int)$_POST['rating'];
        $content = trim($_POST['content']);
        $userId = $_SESSION['user_id'];

        // Validation: whether the rating is 1-5, whether the player owns the game and whether they haven't rated it yet
        if ($rating >= 1 && $rating <= 5 && $this->libraryRepository->isGameOwned($userId, $gameId) && !$this->reviewRepository->hasUserReviewed($userId, $gameId)) {
            $review = new Review(null, $userId, $gameId, null, null, $rating, $content, null);
            $this->reviewRepository->addReview($review);
        }

        // Redirection back to the game page
        header("Location: /game/" . $gameId);
        exit();
    }

    // Endpoint for handling heart clicks (Fetch API)
    public function toggleWishlist() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // If not logged in, we return an error and a link to log in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized', 'redirect' => '/login']);
            return;
        }

        if (!$this->isPost()) {
            http_response_code(405);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $gameId = $data['gameId'] ?? null;

        if (!$gameId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid game ID']);
            return;
        }

        try {
            $action = $this->gameRepository->toggleWishlist($_SESSION['user_id'], $gameId);
            http_response_code(200);
            echo json_encode(['success' => true, 'action' => $action]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }
}