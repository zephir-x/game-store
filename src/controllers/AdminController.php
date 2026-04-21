<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/GameRepository.php';

class AdminController extends AppController {
    private UserRepository $userRepository;
    private GameRepository $gameRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->gameRepository = new GameRepository();
    }

    // the main method for displaying the admin dashboard with sorting and filtering
    public function index() {
        $this->checkAdmin();
        $currentUserId = $_SESSION['user_id'];

        // extract query parameters directly from the uri to bypass router limitations
        $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '';
        parse_str($queryString, $queryParams);

        // determine active tab and sorting defaults
        $activeTab = $queryParams['tab'] ?? 'users';
        $defaultSort = ($activeTab === 'games') ? 'game_id' : 'id';
        
        $sort = $queryParams['sort'] ?? $defaultSort;
        $dir = isset($queryParams['dir']) && $queryParams['dir'] === 'desc' ? 'DESC' : 'ASC';

        // prepare filters for games
        $gameFilters = [
            'min_id' => $queryParams['min_id'] ?? null,
            'max_id' => $queryParams['max_id'] ?? null,
            'min_price' => $queryParams['min_price'] ?? null,
            'max_price' => $queryParams['max_price'] ?? null,
            'min_rating' => $queryParams['min_rating'] ?? null,
            'max_rating' => $queryParams['max_rating'] ?? null,
            'min_reviews' => $queryParams['min_reviews'] ?? null,
            'max_reviews' => $queryParams['max_reviews'] ?? null,
            'filtered' => $queryParams['filtered'] ?? null
        ];

        // prepare filters for users
        $userFilters = [
            'min_id' => $queryParams['min_id'] ?? null,
            'max_id' => $queryParams['max_id'] ?? null,
            'min_date' => $queryParams['min_date'] ?? null,
            'max_date' => $queryParams['max_date'] ?? null,
            'role_user' => $queryParams['role_user'] ?? null,
            'role_admin' => $queryParams['role_admin'] ?? null,
            'filtered' => $queryParams['filtered'] ?? null
        ];

        $emptyFilters = [];

        // fetch filtered data based on the active section
        if ($activeTab === 'games') {
            $games = $this->gameRepository->getAllGamesFiltered($gameFilters, $sort, $dir);
            $users = $this->userRepository->getAllUsersFiltered($currentUserId, $emptyFilters, 'id', 'ASC');
        } else {
            $games = $this->gameRepository->getAllGamesFiltered($emptyFilters, 'game_id', 'ASC');
            $users = $this->userRepository->getAllUsersFiltered($currentUserId, $userFilters, $sort, $dir);
        }

        return $this->render('admins/admin', [
            'title' => 'Admin Panel - GameNest',
            'games' => $games,
            'users' => $users,
            'activeTab' => $activeTab
        ]);
    }

    // Updating user information (almost the same as in Dashboard, but for a specific ID)
    public function updateUser() {
        $this->checkAdmin();
        
        if ($this->isPost() && isset($_POST['user_id'])) {
            $userId = (int)$_POST['user_id'];
            
            $data = [
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '', 
                'name' => trim($_POST['name'] ?? ''),
                'surname' => trim($_POST['surname'] ?? ''),
                'bio' => trim($_POST['bio'] ?? ''),
                'avatar' => $_POST['avatar'] ?? 'gaming-console.jpg'
            ];

            try {
                $this->userRepository->adminUpdateUser($userId, $data);
                $_SESSION['success_message'] = "User #$userId updated successfully!";
            } catch (PDOException $e) {
                if ($e->getCode() == 23505) {
                    $_SESSION['error_message'] = "Email or username already in use by another account!";
                } else {
                    $_SESSION['error_message'] = "Error updating user data.";
                }
            }
        }
        
        header("Location: /edit-user/" . ($userId ?? ''));
        exit();
    }

    // Displaying the edit form for the selected user
    public function editUser($id = null) {
        $this->checkAdmin();
        
        if (!$id) {
            header("Location: /admin");
            exit();
        }

        // Fetch the data of the selected user from the repository
        $user = $this->userRepository->getUserById((int)$id);
        $userDetails = $this->userRepository->getUserDetails((int)$id);
        
        if (!$user) {
            header("Location: /admin");
            exit();
        }

        // Fetch the list of available avatars
        $avatars = $this->getAvailableAvatars();

        return $this->render("admins/admin_user_form", [
            "title" => "Edit User - GameNest",
            "editedUser" => $user,
            "details" => $userDetails,
            "avatars" => $avatars
        ]);
    }

    public function changeRole() {
        $this->checkAdmin();
        
        if ($this->isPost() && isset($_POST['user_id']) && isset($_POST['role'])) {
            $userId = (int)$_POST['user_id'];
            $newRole = $_POST['role'];
            
            // Security: Admin cannot change role to himself
            if ($userId !== $_SESSION['user_id'] && in_array($newRole, ['USER', 'ADMIN'])) {
                $this->userRepository->updateUserRole($userId, $newRole);
            }
        }
        header("Location: /admin");
        exit();
    }

    public function deleteUser() {
        $this->checkAdmin();
        
        if ($this->isPost() && isset($_POST['user_id'])) {
            $userIdToDelete = (int)$_POST['user_id'];
            
            // Security: Admin cannot delete himself
            if ($userIdToDelete !== $_SESSION['user_id']) {
                $this->userRepository->deleteUser($userIdToDelete);
            }
        }
        header("Location: /admin");
        exit();
    }

    // Displays the empty form for a new game
    public function addGame() {
        $this->checkAdmin();
        return $this->render("admins/admin_game_form", [
            "title" => "Add New Game - GameNest",
            "game" => null // means the form will be empty
        ]);
    }

    // Displays the filled form for an existing game
    public function editGame($id = null) {
        $this->checkAdmin();
        if (!$id) { header("Location: /admin"); exit(); }
        
        $game = $this->gameRepository->getGameById((int)$id);
        if (!$game) { header("Location: /admin"); exit(); }

        return $this->render("admins/admin_game_form", [
            "title" => "Edit Game - GameNest",
            "game" => $game // we pass the game object to the view
        ]);
    }

    // Universal method for saving (Adding and Editing)
    public function saveGame() {
        $this->checkAdmin();
        
        if ($this->isPost()) {
            // 400: Does the request really have a title and price?
            if (!isset($_POST['title']) || !isset($_POST['price'])) {
                $this->abort(400); // Critical script termination and 400 error page
            }

            // We collect text data
            $id = !empty($_POST['game_id']) ? (int)$_POST['game_id'] : null;
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $category = trim($_POST['category']);
            $price = (float)$_POST['price'];

            // We collect individual fields from the form
            $specsArray = [
                'minimum' => [
                    'os' => trim($_POST['min_os'] ?? ''),
                    'cpu' => trim($_POST['min_cpu'] ?? ''),
                    'gpu' => trim($_POST['min_gpu'] ?? ''),
                    'ram' => trim($_POST['min_ram'] ?? '') ?: 'not specified',
                    'storage' => trim($_POST['min_storage'] ?? '') ?: 'not specified'
                ],
                'recommended' => [
                    'os' => trim($_POST['rec_os'] ?? '') ?: 'not specified',
                    'cpu' => trim($_POST['rec_cpu'] ?? '') ?: 'not specified',
                    'gpu' => trim($_POST['rec_gpu'] ?? '') ?: 'not specified',
                    'ram' => trim($_POST['rec_ram'] ?? '') ?: 'not specified',
                    'storage' => trim($_POST['rec_storage'] ?? '') ?: 'not specified'
                ]
            ];
        
            $specification = json_encode($specsArray); // we compress it to JSON format
            $developer = trim($_POST['developer'] ?? 'Unknown Studio');
            $releaseDate = trim($_POST['release_date'] ?? date('Y-m-d'));
            
            // Validation: price cannot be negative
            if ($price < 0) {
                $_SESSION['error_message'] = "Price cannot be negative!";
                // We determine where to move it back (for editing or for adding)
                $redirectUrl = $id ? "/edit-game/$id" : "/add-game";
                header("Location: " . $redirectUrl);
                exit();
            }

            // Graphics support
            $graphics = !empty($_POST['existing_graphics']) ? trim($_POST['existing_graphics']) : 'default.jpg';
            
            if (isset($_FILES['graphics']) && $_FILES['graphics']['error'] === UPLOAD_ERR_OK) {
                $newGraphics = $this->handleFileUpload($_FILES['graphics']);
                
                // Security: is_file() makes sure we are deleting a file, not a folder
                if ($graphics !== 'default.jpg' && $newGraphics !== 'default.jpg') {
                    $oldFilePath = __DIR__ . '/../../public/resources/covers/' . $graphics;
                    if (is_file($oldFilePath)) { 
                        unlink($oldFilePath); 
                    }
                }
                $graphics = $newGraphics;
            }

            // We create an object (the average rating at the start is 0.0, the trigger in the database will overwrite it if necessary)
            $game = new Game($id ?? 0, $title, $description, $category, $price, $graphics, 0.0, $specification, $developer, $releaseDate);

            try {
                if ($id) {
                    $this->gameRepository->updateGame($game); 
                    $_SESSION['success_message'] = "Game updated successfully!";
                } else {
                    $this->gameRepository->addGame($game);    
                    $_SESSION['success_message'] = "New game added to the store!";
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23505) {
                    $_SESSION['error_message'] = "A game with this title already exists!";
                } else {
                    $_SESSION['error_message'] = "An error occurred while saving the game.";
                }
            }
        }
        
        header("Location: /admin");
        exit();
    }

    // Helper function: Saves a file to disk and returns its new name
    private function handleFileUpload(array $file): string {
        $uploadDir = __DIR__ . '/../../public/resources/covers/';
        
        // We download the file extension (e.g. jpg, png)
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // We generate a unique name (so that games named 'cover.jpg' don't overwrite each other)
        $fileName = uniqid('cover_') . '.' . $fileExt;
        
        $destination = $uploadDir . $fileName;

        // We move the file from the server's temporary folder to our target one
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $fileName;
        }
        
        return 'default.jpg'; // in case of a write error
    }

    public function deleteGame() {
        $this->checkAdmin();
        if ($this->isPost() && isset($_POST['game_id'])) {
            $gameId = (int)$_POST['game_id'];
            
            // First, we fetch the game to know its file name
            $game = $this->gameRepository->getGameById($gameId);
            
            if ($game) {
                $graphics = $game->getGraphics();
                
                // We remove the game from the database
                $this->gameRepository->deleteGame($gameId);
                
                // Delete the file from the disk (if it's not the default image)
                if ($graphics && $graphics !== 'default.jpg') {
                    $filePath = __DIR__ . '/../../public/resources/covers/' . $graphics;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        }
        header("Location: /admin");
        exit();
    }
}