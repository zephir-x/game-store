<?php

class AppController {
    // Check if current request method is GET
    protected function isGet(): bool {
        return $_SERVER["REQUEST_METHOD"] === 'GET';
    }

    // Check if current request method is POST
    protected function isPost(): bool {
        return $_SERVER["REQUEST_METHOD"] === 'POST';
    }

    // Checks if the user is authenticated - if not, redirects them to the login page
    protected function checkAuth(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // If user_id is not present in session, they are not logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error_message'] = "You must be logged in to access this page.";
            header("Location: /login");
            exit();
        }
    }

    // Checks if the user is an Admin. If not, throws a 403 error.
    protected function checkAdmin(): void {
        $this->checkAuth(); // First, make sure you are logged in

        if ($_SESSION['user_role'] !== 'ADMIN') {
            http_response_code(403);
            $this->render('errors/403', ['title' => '403 Forbidden']);
            exit();
        }
    }

    // Stops the application and renders an error page (e.g. 400, 404, 500)
    protected function abort(int $code) {
        http_response_code($code);
        $this->render("errors/$code", ['title' => "$code - Error"]);
        exit(); // Critical script termination
    }

    // Render view template with optional variables
    protected function render(string $template = null, array $variables = []) {
        $templatePath = 'public/views/' . $template . '.html';
        $templatePath404 = 'public/views/errors/404.html';
        $output = "";

        // If template exists, load it with passed variables
        if (file_exists($templatePath)) {
            // Convert array keys into variables for template usage
            extract($variables);
            ob_start();
            include $templatePath;
            $output = ob_get_clean();

        } else {
            // Fallback to 404 page if template not found
            ob_start();
            include $templatePath404;
            $output = ob_get_clean();
        }
        
        // Output final rendered HTML
        echo $output;
    }

    // Avatar reading function
    protected function getAvailableAvatars(): array {
        $dir = __DIR__ . '/../../public/resources/avatars/';
        $avatars = [];
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'])) {
                    $avatars[] = $file;
                }
            }
        }
        return empty($avatars) ? ['gaming-console.jpg'] : $avatars;
    }
}