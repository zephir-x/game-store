<?php

require_once 'AppController.php';

class DashboardController extends AppController {
    
    // Main dashboard view handler
    public function index($id = null) {
        // Path Security - kicks out non-logged-in users to /login
        $this->checkAuth();

        // Since we passed checkAuth, this means the user is logged in - we can safely retrieve their data from the session
        $data = [
            "username" => $_SESSION['username'],
            "role" => $_SESSION['user_role'],
            "selectedId" => $id
        ];

        // Render dashboard view with passed variables
        return $this->render("dashboard", [
            "title" => "Dashboard",
            "data" => $data
        ]);
    }
}