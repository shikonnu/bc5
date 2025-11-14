<?php
// ==================== ADMIN AUTHENTICATION ====================
session_start();

// Use absolute path for database
require_once __DIR__ . '/../config/database.php';

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        if (!headers_sent()) {
            header('Location: /admin/login.php');
            exit;
        }
    }
}

function login($username, $password) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, username, password FROM users WHERE username = :username AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            return true;
        }
    }
    return false;
}

function logout() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// Create users table if not exists
function createUsersTable() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    try {
        $db->exec($query);
        
        // Create default admin user if not exists
        $check_query = "SELECT COUNT(*) FROM users WHERE username = 'admin'";
        $stmt = $db->query($check_query);
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $insert_query = "INSERT INTO users (username, password) VALUES (:username, :password)";
            $stmt = $db->prepare($insert_query);
            $default_password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt->execute([':username' => 'admin', ':password' => $default_password]);
        }
    } catch (Exception $e) {
        // Table creation might fail if it already exists, that's fine
    }
}

// Initialize table
createUsersTable();
?>
