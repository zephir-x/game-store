<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/GameRepository.php';

class StoreController extends AppController {
    private GameRepository $gameRepository;

    public function __construct() {
        $this->gameRepository = new GameRepository();
    }

    public function index() {
        // We are extracting games from the database
        $games = $this->gameRepository->getGames();

        // We are passing them to the view
        return $this->render("index", [
            "title" => "Store - GameNest",
            "games" => $games
        ]);
    }
}