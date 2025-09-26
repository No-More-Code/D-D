<?php
// users.php
require_once 'config.php';

header('Content-Type: application/json');

$user = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getUsers();
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function getUsers() {
    global $pdo, $user;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, username, email, join_date, last_seen, is_online
            FROM users 
            WHERE id != ?
            ORDER BY username ASC
        ");
        $stmt->execute([$user['id']]);
        $users = $stmt->fetchAll();
        
        echo json_encode($users);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}
?>