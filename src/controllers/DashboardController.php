<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/LibraryRepository.php';

class DashboardController extends AppController {
    private UserRepository $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    // Main dashboard view handler
    public function index($id = null) {
        // Path Security - kicks out non-logged-in users to /login
        $this->checkAuth();

        $userId = $_SESSION['user_id'];

        // Downloading data
        $userDetails = $this->userRepository->getUserDetails($userId);

        // Render dashboard view with passed variables
        return $this->render("dashboard", [
            "title" => "Profile - GameNest",
            "username" => $_SESSION['username'],
            "role" => $_SESSION['user_role'],
            "details" => $userDetails
        ]);
    }
}