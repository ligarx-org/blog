<?php
// Ultra simple API test
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once 'config.php';
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Simple test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'message' => 'API working perfectly!',
        'users' => $userCount,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>