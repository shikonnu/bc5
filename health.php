<?php
// ==================== HEALTH CHECK ENDPOINT ====================
// This file is used by Render to check if the service is healthy

// Set response headers
header('Content-Type: text/plain');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Return 200 OK status
http_response_code(200);

// Simple response
echo "OK - Service Healthy\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";

// Optional: Test database connection
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Simple query to test database
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['test'] == 1) {
        echo "Database: Connected ✓\n";
    } else {
        echo "Database: Query failed ✗\n";
    }
} catch (Exception $e) {
    echo "Database: Error - " . $e->getMessage() . " ✗\n";
}

// Optional: Check if essential files exist
$essential_files = ['index.php', 'panel.php', 'config/database.php'];
foreach ($essential_files as $file) {
    if (file_exists($file)) {
        echo "File $file: Exists ✓\n";
    } else {
        echo "File $file: Missing ✗\n";
    }
}
?>