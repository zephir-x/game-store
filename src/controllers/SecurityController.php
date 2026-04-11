<?php

require_once 'AppController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class SecurityController extends AppController {
    private UserRepository $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    public function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // If the user is already logged in, throw him to the dashboard
        if (isset($_SESSION['user_id'])) {
            header("Location: /dashboard");
            exit();
        }

        if (!$this->isPost()) {
            return $this->render('login');
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        $user = $this->userRepository->getUser($email);

        // Verification: does the user exist and does the password match the hash in the database?
        if (!$user || !password_verify($password, $user->getPassword())) {
            return $this->render('login', ['messages' => ['Invalid email or password!']]);
        }

        // Protection against Session Fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_role'] = $user->getRole();
        $_SESSION['username'] = $user->getUsername();

        header("Location: /dashboard");
        exit();
    }

    public function register() {
        if (!$this->isPost()) {
            return $this->render('register');
        }

        $email = $_POST['email'];
        $password = $_POST['password'];
        $username = $_POST['username'];

        // Validation (for now basic, can be expanded)
        if (empty($email) || empty($password) || empty($username)) {
            return $this->render('register', ['messages' => ['All fields are required!']]);
        }

        // Hashing the password using the built-in function (also generates salt)
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $user = new User($email, $hashedPassword, $username);
        
        try {
            $this->userRepository->addUser($user);
            return $this->render('login', ['messages' => ['Account created! You can now log in.']]);
        } catch (PDOException $e) {
            // Capturing a duplicate error from the database (e.g., email already exists)
            return $this->render('register', ['messages' => ['Username or email already exists!']]);
        }
    }

    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        header("Location: /");
        exit();
    }
}