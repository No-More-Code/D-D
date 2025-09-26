<?php
// realtime.php - Server-Sent Events for real-time updates
require_once 'config.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Cache-Control');

$user = requireAuth();

// Update user's online status and last activity
try {
    $stmt = $pdo->prepare("UPDATE users SET is_online = TRUE, last_seen = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Track active session
    $sessionId = session_id();
    $stmt = $pdo->prepare("
        INSERT INTO active_sessions (user_id, session_id) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE last_activity = NOW()
    ");
    $stmt->execute([$user['id'], $sessionId]);
} catch (PDOException $e) {
    // Continue even if session tracking fails
}

// Function to send SSE data
function sendSSE($event, $data) {
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Keep track of last message IDs to only send new messages
$lastChatMessageId = $_GET['last_chat_id'] ?? 0;
$lastDirectMessageId = $_GET['last_direct_id'] ?? 0;

// Send initial connection confirmation
sendSSE('connected', ['user_id' => $user['id'], 'username' => $user['username']]);

// Main loop for real-time updates
$loopCount = 0;
while (true) {
    try {
        // Check for new chat messages
        $stmt = $pdo->prepare("
            SELECT cm.id, cm.message, cm.timestamp, u.username, u.id as user_id
            FROM chat_messages cm
            JOIN users u ON cm.user_id = u.id
            WHERE cm.id > ?
            ORDER BY cm.timestamp ASC
        ");
        $stmt->execute([$lastChatMessageId]);
        $newChatMessages = $stmt->fetchAll();
        
        foreach ($newChatMessages as $message) {
            sendSSE('chat_message', $message);
            $lastChatMessageId = max($lastChatMessageId, $message['id']);
        }
        
        // Check for new direct messages (sent to this user)
        $stmt = $pdo->prepare("
            SELECT dm.id, dm.message, dm.timestamp, dm.is_read,
                   sender.username as sender_username, sender.id as sender_id,
                   recipient.username as recipient_username, recipient.id as recipient_id
            FROM direct_messages dm
            JOIN users sender ON dm.sender_id = sender.id
            JOIN users recipient ON dm.recipient_id = recipient.id
            WHERE dm.id > ? AND dm.recipient_id = ?
            ORDER BY dm.timestamp ASC
        ");
        $stmt->execute([$lastDirectMessageId, $user['id']]);
        $newDirectMessages = $stmt->fetchAll();
        
        foreach ($newDirectMessages as $message) {
            sendSSE('direct_message', $message);
            $lastDirectMessageId = max($lastDirectMessageId, $message['id']);
        }
        
        // Every 10 loops (~30 seconds), send user status updates and cleanup
        if ($loopCount % 10 == 0) {
            // Clean up old sessions (users offline for more than 5 minutes)
            $stmt = $pdo->prepare("
                UPDATE users SET is_online = FALSE 
                WHERE id IN (
                    SELECT user_id FROM active_sessions 
                    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                )
            ");
            $stmt->execute();
            
            // Delete old session records
            $stmt = $pdo->prepare("
                DELETE FROM active_sessions 
                WHERE last_activity < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            ");
            $stmt->execute();
            
            // Get online users and send status update
            $stmt = $pdo->prepare("
                SELECT id, username, is_online, last_seen 
                FROM users 
                WHERE is_online = TRUE AND id != ?
            ");
            $stmt->execute([$user['id']]);
            $onlineUsers = $stmt->fetchAll();
            
            sendSSE('user_status', ['online_users' => $onlineUsers]);
            
            // Send heartbeat to keep connection alive
            sendSSE('heartbeat', ['timestamp' => time()]);
        }
        
        // Update this user's last activity
        if ($loopCount % 5 == 0) { // Every 15 seconds
            $stmt = $pdo->prepare("
                UPDATE active_sessions SET last_activity = NOW() 
                WHERE user_id = ? AND session_id = ?
            ");
            $stmt->execute([$user['id'], $sessionId]);
        }
        
        $loopCount++;
        
        // Check if client disconnected
        if (connection_aborted()) {
            break;
        }
        
        // Sleep for 3 seconds before checking again
        sleep(3);
        
    } catch (PDOException $e) {
        sendSSE('error', ['message' => 'Database error']);
        sleep(5); // Wait longer on error
    } catch (Exception $e) {
        sendSSE('error', ['message' => 'Server error']);
        break;
    }
}

// Cleanup when connection ends
try {
    $stmt = $pdo->prepare("UPDATE users SET is_online = FALSE WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    $stmt = $pdo->prepare("DELETE FROM active_sessions WHERE user_id = ? AND session_id = ?");
    $stmt->execute([$user['id'], $sessionId]);
} catch (PDOException $e) {
    // Ignore cleanup errors
}
?>