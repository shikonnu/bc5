<?php
// ==================== ADMIN LOGIN PAGE ====================
session_start();
require_once __DIR__ . '/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (!headers_sent()) {
        header('Location: /panel.php');
        exit;
    }
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        if (login($username, $password)) {
            if (!headers_sent()) {
                header('Location: /panel.php');
                exit;
            }
        } else {
            $error = 'Invalid username or password';
        }
    } else {
        $error = 'Please enter both username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Spamir Antibot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .login-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        .brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .brand-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <div class="brand-icon">🛡️</div>
            <h4>Spamir Antibot</h4>
            <p class="grey-text">Admin Control Panel</p>
        </div>
        
        <?php if ($error): ?>
            <div class="card-panel red lighten-4 red-text">
                <i class="material-icons left">error</i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-field">
                <i class="material-icons prefix">person</i>
                <input id="username" type="text" name="username" required>
                <label for="username">Username</label>
            </div>
            
            <div class="input-field">
                <i class="material-icons prefix">lock</i>
                <input id="password" type="password" name="password" required>
                <label for="password">Password</label>
            </div>
            
            <div class="center-align" style="margin-top: 2rem;">
                <button type="submit" class="btn waves-effect waves-light">
                    <i class="material-icons left">login</i>
                    Login
                </button>
            </div>
        </form>
        
        <div class="center-align" style="margin-top: 2rem;">
            <div class="card-panel blue lighten-5">
                <h6>Default Credentials</h6>
                <p><strong>Username:</strong> admin</p>
                <p><strong>Password:</strong> admin123</p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        // Initialize Materialize components
        document.addEventListener('DOMContentLoaded', function() {
            M.updateTextFields();
        });
    </script>
</body>
</html>
