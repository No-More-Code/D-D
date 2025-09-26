<?php
// messages.php
require_once 'config.php';

header('Content-Type: application/json');

$user = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        $type = $_GET['type'] ?? 'chat';
        
        if ($type === 'chat') {
            getChatMessages();
        } elseif ($type === 'direct') {
            getDirectMessages();
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid message type']);
        }
        break;
        
    case 'POST':
        $type = $_GET['type'] ?? 'chat';
        
        if ($type === 'chat') {
            sendChatMessage();
        } elseif ($type === 'direct') {
            sendDirectMessage();
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid message type']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function getChatMessages() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT cm.id, cm.message, cm.timestamp, u.username, u.id as user_id
            FROM chat_messages cm
            JOIN users u ON cm.user_id = u.id
            ORDER BY cm.timestamp DESC
            LIMIT 100
        ");
        $stmt->execute();
        $messages = $stmt->fetchAll();
        
        // Reverse to show oldest first
        $messages = array_reverse($messages);
        
        echo json_encode($messages);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function sendChatMessage() {
    global $pdo, $user, $input;
    
    $message = trim($input['message'] ?? '');
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message cannot be empty']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, message) VALUES (?, ?)");
        $stmt->execute([$user['id'], $message]);
        
        $messageId = $pdo->lastInsertId();
        
        // Get the complete message data
        $stmt = $pdo->prepare("
            SELECT cm.id, cm.message, cm.timestamp, u.username, u.id as user_id
            FROM chat_messages cm
            JOIN users u ON cm.user_id = u.id
            WHERE cm.id = ?
        ");
        $stmt->execute([$messageId]);
        $messageData = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => $messageData
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function getDirectMessages() {
    global $pdo, $user;
    
    $otherUserId = $_GET['user_id'] ?? '';
    
    if (empty($otherUserId)) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT dm.id, dm.message, dm.timestamp, dm.is_read,
                   sender.username as sender_username, sender.id as sender_id,
                   recipient.username as recipient_username, recipient.id as recipient_id
            FROM direct_messages dm
            JOIN users sender ON dm.sender_id = sender.id
            JOIN users recipient ON dm.recipient_id = recipient.id
            WHERE (dm.sender_id = ? AND dm.recipient_id = ?) 
               OR (dm.sender_id = ? AND dm.recipient_id = ?)
            ORDER BY dm.timestamp ASC
            LIMIT 100
        ");
        $stmt->execute([$user['id'], $otherUserId, $otherUserId, $user['id']]);
        $messages = $stmt->fetchAll();
        
        // Mark messages as read
        $stmt = $pdo->prepare("
            UPDATE direct_messages 
            SET is_read = TRUE 
            WHERE recipient_id = ? AND sender_id = ?
        ");
        $stmt->execute([$user['id'], $otherUserId]);
        
        echo json_encode($messages);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function sendDirectMessage() {
    global $pdo, $user, $input;
    
    $recipientId = $input['recipient_id'] ?? '';
    $message = trim($input['message'] ?? '');
    
    if (empty($recipientId) || empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Recipient ID and message required']);
        return;
    }
    
    try {
        // Check if recipient exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$recipientId]);
        
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Recipient not found']);
            return;
        }
        
        $stmt = $pdo->prepare("INSERT INTO direct_messages (sender_id, recipient_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $recipientId, $message]);
        
        $messageId = $pdo->lastInsertId();
        
        // Get the complete message data
        $stmt = $pdo->prepare("
            SELECT dm.id, dm.message, dm.timestamp, dm.is_read,
                   sender.username as sender_username, sender.id as sender_id,
                   recipient.username as recipient_username, recipient.id as recipient_id
            FROM direct_messages dm
            JOIN users sender ON dm.sender_id = sender.id
            JOIN users recipient ON dm.recipient_id = recipient.id
            WHERE dm.id = ?
        ");
        $stmt->execute([$messageId]);
        $messageData = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => $messageData
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}
?>