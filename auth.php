<?php
// auth.php
require_once 'config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        $action = $_GET['action'] ?? '';
        
        if ($action === 'register') {
            register();
        } elseif ($action === 'login') {
            login();
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function register() {
    global $pdo, $input;
    
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $email = trim($input['email'] ?? '');
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        return;
    }
    
    if (strlen($username) < 3) {
        http_response_code(400);
        echo json_encode(['error' => 'Username must be at least 3 characters']);
        return;
    }
    
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters']);
        return;
    }
    
    try {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Username already exists']);
            return;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $email]);
        
        $userId = $pdo->lastInsertId();
        
        // Generate JWT token
        $payload = [
            'id' => $userId,
            'username' => $username,
            'exp' => time() + (7 * 24 * 60 * 60) // 7 days
        ];
        $token = generateJWT($payload);
        
        // Get user data
        $stmt = $pdo->prepare("SELECT id, username, email, join_date FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        echo json_encode([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => $user
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function login() {
    global $pdo, $input;
    
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        return;
    }
    
    try {
        // Get user
        $stmt = $pdo->prepare("SELECT id, username, password, email, join_date FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }
        
        // Update last seen and online status
        $stmt = $pdo->prepare("UPDATE users SET last_seen = NOW(), is_online = TRUE WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Generate JWT token
        $payload = [
            'id' => $user['id'],
            'username' => $user['username'],
            'exp' => time() + (7 * 24 * 60 * 60) // 7 days
        ];
        $token = generateJWT($payload);
        
        // Remove password from response
        unset($user['password']);
        
        echo json_encode([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}
?>