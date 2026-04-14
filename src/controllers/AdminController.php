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

    public function index() {
        $this->checkAdmin(); // Ensure only admins can access this page
        
        $users = $this->userRepository->getAllUsers($_SESSION['user_id']);
        $games = $this->gameRepository->getGames();
        
        return $this->render("admin", [
            "title" => "Admin Panel - GameNest",
            "users" => $users,
            "games" => $games
        ]);
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

        return $this->render("admin_user_form", [
            "title" => "Edit User - GameNest",
            "editedUser" => $user,
            "details" => $userDetails,
            "avatars" => $avatars
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
                'avatar' => $_POST['avatar'] ?? 'gaming-console.png'
            ];

            try {
                $this->userRepository->updateFullProfile($userId, $data);
                $_SESSION['success_message'] = "User profile updated!";
            } catch (PDOException $e) {
                if ($e->getCode() == 23505) {
                    $_SESSION['error_message'] = "Email or username already in use by another account!";
                } else {
                    $_SESSION['error_message'] = "Error updating user data.";
                }
            }
        }
        
        header("Location: /admin");
        exit();
    }

    // Displays the empty form for a new game
    public function addGame() {
        $this->checkAdmin();
        return $this->render("admin_game_form", [
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

        return $this->render("admin_game_form", [
            "title" => "Edit Game - GameNest",
            "game" => $game // we pass the game object to the view
        ]);
    }

    // Universal method for saving (Adding and Editing)
    public function saveGame() {
        $this->checkAdmin();
        
        if ($this->isPost()) {
            // We collect text data
            $id = !empty($_POST['game_id']) ? (int)$_POST['game_id'] : null;
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $category = trim($_POST['category']);
            $price = (float)$_POST['price'];
            $specification = trim($_POST['specification']) ?: null;
            
            // Graphics support
            $graphics = $_POST['existing_graphics'] ?? 'default.jpg';
            
            // We check if a new file has been uploaded and if there are no errors
            if (isset($_FILES['graphics']) && $_FILES['graphics']['error'] === UPLOAD_ERR_OK) {
                $newGraphics = $this->handleFileUpload($_FILES['graphics']);
                
                // If a new file was uploaded and the old one was not the default - we delete the old one
                if ($graphics !== 'default.jpg' && $newGraphics !== 'default.jpg') {
                    $oldFilePath = __DIR__ . '/../../public/resources/covers/' . $graphics;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath); // physically delete the file
                    }
                }

                $graphics = $newGraphics; // replace the name with the new one
            }

            // We create an object (the average rating at the start is 0.0, the trigger in the database will overwrite it if necessary)
            $game = new Game($id ?? 0, $title, $description, $category, $price, $graphics, 0.0, $specification);

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