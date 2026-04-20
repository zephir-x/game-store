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
        $isOnWishlist = false;
        $hasReviewed = false;
        $userReviewData = null;

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $isOwned = $this->libraryRepository->isGameOwned($userId, $game->getId());
            $isOnWishlist = $this->gameRepository->isGameOnWishlist($userId, $game->getId());
            $hasReviewed = $this->reviewRepository->hasUserReviewed($userId, $game->getId());

            // If he rated, we download his old review
            if ($hasReviewed) {
                $userReviewData = $this->reviewRepository->getUserReviewForGame($id, $userId);
            }
        }

        $reviews = $this->reviewRepository->getReviewsForGame($game->getId(), $_SESSION['user_id'] ?? null);

        return $this->render("game_details", [
            "title" => $game->getTitle() . " - GameNest",
            "game" => $game,
            "isOwned" => $isOwned,
            "isOnWishlist" => $isOnWishlist,
            "hasReviewed" => $hasReviewed,
            'userReviewData' => $userReviewData,
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
        $gameId = (int)($_POST['game_id'] ?? 0);

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
        if (!$this->isPost()) return;

        $gameId = (int)$_POST['game_id'];
        $rating = (int)$_POST['rating'];
        $content = trim($_POST['content']);
        $isEdit = (int)($_POST['is_edit'] ?? 0); // checking the flag from HTML
        $userId = $_SESSION['user_id'];

        // Safety: You can only review games you own.
        if (!$this->libraryRepository->isGameOwned($userId, $gameId)) {
            $_SESSION['error_message'] = "You must own the game to review it.";
            header("Location: /game/$gameId");
            exit();
        }

        try {
            if ($isEdit === 1) {
                // Edit mode: Updating an old review
                $this->reviewRepository->updateReview($gameId, $userId, $rating, $content);
                $_SESSION['success_message'] = "Your review has been updated!";
            } else {
                // New mode: Checking if the user has already reviewed (protection) and adding
                if ($this->reviewRepository->hasUserReviewed($gameId, $userId)) {
                    $_SESSION['error_message'] = "You have already reviewed this game.";
                } else {
                    $this->reviewRepository->addReview($gameId, $userId, $rating, $content);
                    $_SESSION['success_message'] = "Review added successfully!";
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "An error occurred while saving your review.";
        }

        header("Location: /game/$gameId");
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

    public function toggleReviewLike() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized', 'redirect' => '/login']);
            return;
        }

        if (!$this->isPost()) return;

        $data = json_decode(file_get_contents('php://input'), true);
        $reviewId = (int)($data['reviewId'] ?? 0);

        if (!$reviewId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid review ID']);
            return;
        }

        try {
            $action = $this->reviewRepository->toggleLike($_SESSION['user_id'], $reviewId);
            echo json_encode(['success' => true, 'action' => $action]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }
}