<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class DashboardController extends AppController {
    private UserRepository $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    public function index() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        
        $userDetails = $this->userRepository->getUserDetails($userId);
        
        // Clean retrieval of user data from the Repository
        $mainUser = $this->userRepository->getUserById($userId);

        // We read the available avatars from the folder
        $avatars = $this->getAvailableAvatars();

        return $this->render("dashboard", [
            "title" => "Profile - GameNest",
            "username" => $mainUser->getUsername(),
            "email" => $mainUser->getEmail(),
            "role" => $_SESSION['user_role'],
            "details" => $userDetails,
            "avatars" => $avatars
        ]);
    }

    public function updateProfile() {
        $this->checkAuth();
        
        if ($this->isPost()) {
            // 400: Does the request really have a username and email?
            if (!isset($_POST['username']) || !isset($_POST['email'])) {
                $this->abort(400);
            }

            $userId = $_SESSION['user_id'];
            
            $data = [
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '', 
                'name' => trim($_POST['name'] ?? ''),
                'surname' => trim($_POST['surname'] ?? ''),
                'bio' => trim($_POST['bio'] ?? ''),
                'avatar' => $_POST['avatar'] ?? 'gaming-console.png' // updated default avatar file
            ];

            try {
                $this->userRepository->updateFullProfile($userId, $data);
                $_SESSION['user_avatar'] = $_POST['avatar'];
                $_SESSION['username'] = $data['username']; 
                $_SESSION['success_message'] = "Profile updated successfully!";
            } catch (PDOException $e) {
                // Code 23505 indicates a duplicate value (UNIQUE constraint)
                if ($e->getCode() == 23505) {
                    $_SESSION['error_message'] = "This email or username is already taken!";
                } else {
                    $_SESSION['error_message'] = "Failed to update profile. Please try again.";
                }
            }
        }
        
        header("Location: /dashboard");
        exit();
    }
}