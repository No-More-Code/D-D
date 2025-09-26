<?php
// events.php
require_once 'config.php';

header('Content-Type: application/json');

$user = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        getEvents();
        break;
        
    case 'POST':
        createEvent();
        break;
        
    case 'DELETE':
        deleteEvent();
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function getEvents() {
    global $pdo, $user;
    
    $month = $_GET['month'] ?? date('Y-m');
    $year = $_GET['year'] ?? date('Y');
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, description, event_date, created_at
            FROM calendar_events 
            WHERE user_id = ? AND YEAR(event_date) = ? AND MONTH(event_date) = ?
            ORDER BY event_date ASC
        ");
        
        // Parse month parameter
        if (preg_match('/(\d{4})-(\d{1,2})/', $month, $matches)) {
            $year = $matches[1];
            $monthNum = $matches[2];
        } else {
            $monthNum = date('m');
        }
        
        $stmt->execute([$user['id'], $year, $monthNum]);
        $events = $stmt->fetchAll();
        
        // Group events by date
        $groupedEvents = [];
        foreach ($events as $event) {
            $date = $event['event_date'];
            if (!isset($groupedEvents[$date])) {
                $groupedEvents[$date] = [];
            }
            $groupedEvents[$date][] = $event;
        }
        
        echo json_encode($groupedEvents);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function createEvent() {
    global $pdo, $user, $input;
    
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $eventDate = $input['event_date'] ?? '';
    
    if (empty($title) || empty($eventDate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Title and event date required']);
        return;
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO calendar_events (user_id, title, description, event_date) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user['id'], $title, $description, $eventDate]);
        
        $eventId = $pdo->lastInsertId();
        
        // Get the created event
        $stmt = $pdo->prepare("
            SELECT id, title, description, event_date, created_at
            FROM calendar_events 
            WHERE id = ?
        ");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'event' => $event
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function deleteEvent() {
    global $pdo, $user;
    
    $eventId = $_GET['id'] ?? '';
    
    if (empty($eventId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Event ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?");
        $stmt->execute([$eventId, $user['id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Event deleted']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}
?>