<?php

// Central routing class responsible for directing HTTP requests to appropriate controllers and actions
require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/StoreController.php';
require_once 'src/controllers/GameController.php';
require_once 'src/controllers/LibraryController.php';
require_once 'src/controllers/AdminController.php';

class Routing {
    // Store controller instances (Singleton-like behavior)
    private static array $instances = [];

    // Application routes configuration
    public static $routes = [
        "login" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
        "register" => [
            "controller" => "SecurityController",
            "action" => "register"
        ],
        "logout" => [
            "controller" => "SecurityController",
            "action" => "logout"
        ],
        "dashboard" => [
            "controller" => "DashboardController",
            "action" => "index"
        ],
        "update-profile" => [
            "controller" => "DashboardController", 
            "action" => "updateProfile"
        ],
        "library" => [
            "controller" => "LibraryController",
            "action" => "index"
        ],
        "game" => [
            "controller" => "GameController",
            "action" => "show"
        ],
        "buy" => [
            "controller" => "GameController",
            "action" => "buy"
        ],
        "toggle-wishlist" => [
            "controller" => "GameController",
            "action" => "toggleWishlist"
        ],
        "add-review" => [
            "controller" => "GameController",
            "action" => "addReview"
        ],
        "admin" => [
            "controller" => "AdminController", 
            "action" => "index"
        ],
        "edit-user" => [
            "controller" => "AdminController", 
            "action" => "editUser"
        ],
        "update-user" => [
            "controller" => "AdminController", 
            "action" => "updateUser"
        ],
        "change-role" => [
            "controller" => "AdminController", 
            "action" => "changeRole"
        ],
        "delete-user" => [
            "controller" => "AdminController", 
            "action" => "deleteUser"
        ],
        "add-game" => [
            "controller" => "AdminController", 
            "action" => "addGame"
        ],
        "edit-game" => [
            "controller" => "AdminController", 
            "action" => "editGame"
        ],
        "save-game" => [
            "controller" => "AdminController", 
            "action" => "saveGame"
        ],
        "delete-game" => [
            "controller" => "AdminController", 
            "action" => "deleteGame"
        ],
        "" => [
            "controller" => "StoreController",
            "action" => "index"
        ]
    ];

    // Return existing or create new controller instance
    private static function getControllerInstance(string $controllerName) {
        if (!isset(self::$instances[$controllerName])) {
            self::$instances[$controllerName] = new $controllerName();
        }

        return self::$instances[$controllerName];
    }

    // Main routing logic
    public static function run(string $path) {
        // Support for routes like /dashboard/123
        $parts = explode('/', trim($path, '/'));
        $route = $parts[0] ?? '';
        $id = $parts[1] ?? null;

        // Check if route exists
        if (array_key_exists($route, self::$routes)) {

            $controllerName = self::$routes[$route]["controller"];
            $action = self::$routes[$route]["action"];

            // Get controller instance
            $controller = self::getControllerInstance($controllerName);

            // Call action with optional ID parameter
            $controller->$action($id);

        } else {
            // Fallback 404 page
            http_response_code(404);
            $title = "404 - Page Not Found";
            include 'public/views/errors/404.html';
        }
    }
}