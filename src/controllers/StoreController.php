<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/GameRepository.php';

class StoreController extends AppController {
    private GameRepository $gameRepository;

    public function __construct() {
        $this->gameRepository = new GameRepository();
    }

    public function index() {
        // Protection against XSS attacks - if it exists, we clear the string
        $searchQuery = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : null;
        
        // Retrieve games, passing our cleaned string (or null)
        $games = $this->gameRepository->getGames($searchQuery);
        
        return $this->render("store", [
            "title" => "GameNest - Store",
            "games" => $games,
            "searchQuery" => $searchQuery // we pass this to the view so it doesn't disappear from the bar
        ]);
    }
}