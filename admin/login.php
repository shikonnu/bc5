<?php
session_start();
require_once 'auth.php';

if (is_logged_in()) {
    header('Location: ../panel.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header('Location: ../panel.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h4 class="center-align">🔐 Admin Login</h4>
        
        <?php if ($error): ?>
            <div class="card-panel red lighten-4 red-text">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-field">
                <input id="username" type="text" name="username" required>
                <label for="username">Username</label>
            </div>
            
            <div class="input-field">
                <input id="password" type="password" name="password" required>
                <label for="password">Password</label>
            </div>
            
            <div class="center-align">
                <button type="submit" class="btn waves-effect waves-light">
                    Login <i class="material-icons right">lock_open</i>
                </button>
            </div>
        </form>
        
        <div class="center-align" style="margin-top: 20px;">
            <small class="grey-text">Default: admin / admin123</small>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>