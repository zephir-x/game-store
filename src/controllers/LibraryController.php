<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/LibraryRepository.php';

class LibraryController extends AppController {
    
    // The repository responsible for operations on the user's library
    private LibraryRepository $libraryRepository;

    public function __construct() {
        // Initialization of the data access layer (DB/library logic)
        $this->libraryRepository = new LibraryRepository();
    }

    public function index() {
        // Check if the user is logged in (access protection)
        $this->checkAuth();

        // Get the ID of the currently logged-in user from the session
        $userId = $_SESSION['user_id'];
        
        // Get the list of games assigned to the user
        $library = $this->libraryRepository->getUserLibrary($userId);

        // Render the library view + pass data to the view
        return $this->render("library", [
            "title" => "Library - GameNest",
            "library" => $library
        ]);
    }
}