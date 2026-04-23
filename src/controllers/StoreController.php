<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/GameRepository.php';

class StoreController extends AppController {
    private GameRepository $gameRepository;

    public function __construct() {
        $this->gameRepository = new GameRepository();
    }

    public function index() {
        // Parsing the uri to handle both search and advanced filters safely
        $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '';
        parse_str($queryString, $queryParams);

        // Check if user is using the main search bar
        $searchQuery = isset($queryParams['search']) && $queryParams['search'] !== '' 
            ? htmlspecialchars(trim($queryParams['search'])) 
            : null;

        // Initialize variables for our sections
        $featuredGame = null;
        $topRatedGames = [];
        $recommendedGames = [];
        $categories = [];
        $browseGames = [];

        if ($searchQuery) {
            // Search Active - we only fetch games matching the search string, ignoring other sections
            $browseGames = $this->gameRepository->getGames($searchQuery);
        } else {
            // Default Store View - fetch the top sections
            $featuredGame = $this->gameRepository->getFeaturedGame();
            $topRatedGames = $this->gameRepository->getTopRatedGames();
            $recommendedGames = $this->gameRepository->getRecommendedGames();
            $categories = $this->gameRepository->getAllCategories();

            // Setup sorting for 'browse all games' (defaulting to ID descending - newest added)
            $sort = $queryParams['sort'] ?? 'game_id';
            $dir = isset($queryParams['dir']) && $queryParams['dir'] === 'asc' ? 'ASC' : 'DESC';

            /// Collect filters for 'browse all games'
            $storeFilters = [
                'min_id' => $queryParams['min_id'] ?? null,
                'max_id' => $queryParams['max_id'] ?? null,
                'min_price' => $queryParams['min_price'] ?? null,
                'max_price' => $queryParams['max_price'] ?? null,
                'min_rating' => $queryParams['min_rating'] ?? null,
                'max_rating' => $queryParams['max_rating'] ?? null,
                'type_free' => $queryParams['type_free'] ?? null,
                'type_paid' => $queryParams['type_paid'] ?? null,
                'type_upcoming' => $queryParams['type_upcoming'] ?? null,
                'categories' => $queryParams['categories'] ?? null,
                'cat_all' => $queryParams['cat_all'] ?? null,
                'filtered' => $queryParams['filtered'] ?? null
            ];

            // Fetch the filtered list for the bottom section
            $browseGamesData = $this->gameRepository->getAllGamesFiltered($storeFilters, $sort, $dir);
            $browseGames = []; // clean the array before populating with objects

            // We convert raw arrays to Game class objects for the view
            foreach ($browseGamesData as $data) {
                $browseGames[] = new Game(
                    $data['game_id'],
                    $data['title'],
                    $data['description'],
                    $data['category'],
                    (float)$data['price'],
                    $data['graphics'],
                    (float)$data['calculated_rating'],
                    $data['specification'],
                    $data['developer'],
                    $data['release_date'],
                    $data['game_file'] ?? null
                );
            }
        }

        return $this->render("store", [
            "title" => "GameNest - Store",
            "searchQuery" => $searchQuery,
            "featuredGame" => $featuredGame,
            "topRatedGames" => $topRatedGames,
            "recommendedGames" => $recommendedGames,
            "categories" => $categories,
            "games" => $browseGames // this maps to 'Browse All Games'
        ]);
    }
}