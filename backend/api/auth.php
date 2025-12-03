<?php
// Authentication API endpoints
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/auth_errors.log');

header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:8000'));
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Set secure session cookie parameters BEFORE starting session
$hostWithPort = $_SERVER['HTTP_HOST'] ?? 'localhost';
$hostOnly = strpos($hostWithPort, ':') !== false ? explode(':', $hostWithPort)[0] : $hostWithPort;
$cookieDomain = ($hostOnly === 'localhost' || $hostOnly === '127.0.0.1') ? '' : $hostOnly;

$isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] == 443);
$isLocalhost = ($hostOnly === 'localhost' || $hostOnly === '127.0.0.1');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $isSecure ? '1' : '0');
    ini_set('session.cookie_samesite', $isLocalhost ? 'Lax' : 'Strict');

    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $cookieDomain,
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => $isLocalhost ? 'Lax' : 'Strict'
    ]);

    session_start([
        'cookie_lifetime' => 86400,
        'use_strict_mode' => true,
        'use_only_cookies' => 1
    ]);
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/audit.php';

$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
error_log("[AUTH] ===== NEW REQUEST =====");
error_log("[AUTH] Method: $method");
error_log("[AUTH] Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
error_log("[AUTH] Session ID: " . session_id());
error_log("[AUTH] Raw input: " . $rawInput);

$pdo = get_pdo();

// Helper functions
function read_json() {
    global $rawInput;
    $data = json_decode($rawInput, true);
    error_log("[AUTH] Decoded JSON: " . json_encode($data));
    return $data ?: [];
}

function generate_session_token() {
    return bin2hex(random_bytes(32));
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_auth() {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
}

try {
    // Add session timeout check
    $timeout = 1800; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Last request was more than 30 minutes ago
        session_unset();
        session_destroy();
        echo json_encode(['error' => 'Session timed out']);
        exit;
    }
    $_SESSION['last_activity'] = time(); // Update last activity time

    switch ($method) {
        case 'POST':
            $data = read_json();
            $action = $data['action'] ?? '';
            
            switch ($action) {
                case 'login':
                    error_log("[AUTH] Login attempt started");
                    error_log("[AUTH] Received data: " . json_encode($data));
                    
                    $email = $data['email'] ?? '';
                    $password = $data['password'] ?? '';
                    
                    error_log("[AUTH] Email: $email, Password length: " . strlen($password));
                    
                    if (empty($email) || empty($password)) {
                        error_log("[AUTH] FAILED - Empty email or password");
                        http_response_code(400);
                        echo json_encode(['error' => 'Email and password required']);
                        exit;
                    }
                    
                    error_log("[AUTH] Querying database for user: $email");
                    $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(?)');
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if (!$user) {
                        error_log("[AUTH] FAILED - User not found: $email");
                        http_response_code(401);
                        echo json_encode(['error' => 'Invalid credentials']);
                        exit;
                    }
                    
                    error_log("[AUTH] User found, verifying password");
                    if (!password_verify($password, $user['password'])) {
                        error_log("[AUTH] FAILED - Invalid password for: $email");
                        http_response_code(401);
                        echo json_encode(['error' => 'Invalid credentials']);
                        exit;
                    }
                    
                    error_log("[AUTH] Password verified successfully");
                    
                    // Normalize role (trim spaces)
                    $user['role'] = trim($user['role'] ?? '');
                    
                    // Create session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    log_audit_trail('login', 'user', $user['id']);
                    
                    error_log("[AUTH] Session created for user: {$user['id']} - {$user['name']} ({$user['role']})");
                    
                    // Regenerate session ID after login to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Remove password from response
                    unset($user['password']);
                    
                    $response = [
                        'success' => true,
                        'user' => $user,
                        'message' => 'Login successful'
                    ];
                    
                    error_log("[AUTH] SUCCESS - Sending response: " . json_encode($response));
                    echo json_encode($response);
                    break;
                    
                case 'register':
                    $name = $data['name'] ?? '';
                    $email = $data['email'] ?? '';
                    $password = $data['password'] ?? '';
                    $role = $data['role'] ?? 'staff';
                    
                    if (empty($name) || empty($email) || empty($password)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Name, email and password required']);
                        exit;
                    }
                    
                    // Check if email already exists
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
                    $stmt->execute([$email]);
                    if ($stmt->fetchColumn() > 0) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Email already registered']);
                        exit;
                    }
                    
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$name, $email, $hashedPassword, $role]);
                    
                    $userId = $pdo->lastInsertId();
                    
                    log_audit_trail('register', 'user', $userId);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'User registered successfully',
                        'user_id' => $userId
                    ]);
                    break;
                    
                case 'logout':
                    if (is_logged_in()) {
                        log_audit_trail('logout', 'user', $_SESSION['user_id']);
                    }
                    // Destroy session completely on logout
                    $_SESSION = [];
                    if (ini_get("session.use_cookies")) {
                        $params = session_get_cookie_params();
                        setcookie(session_name(), '', time() - 42000,
                            $params["path"], $params["domain"],
                            $params["secure"], $params["httponly"]
                        );
                    }
                    session_destroy();
                    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
                    break;
                    
                case 'timeout_test':
                    // Simulate session timeout for testing
                    $_SESSION['last_activity'] = time() - 1801; // 30 min + 1 second
                    
                    // Check if session should be timed out
                    $timeout = 1800; // 30 minutes
                    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
                        session_unset();
                        session_destroy();
                        http_response_code(440); // Session Timeout
                        echo json_encode(['error' => 'Session timed out']);
                        exit;
                    }
                    
                    echo json_encode(['success' => true]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        case 'GET':
            $action = $_GET['action'] ?? '';
            
            switch ($action) {
                case 'me':
                    if (!is_logged_in()) {
                        echo json_encode(null);
                        exit;
                    }
                    
                    $stmt = $pdo->prepare('SELECT id, name, email, role, phone, notes, created_at FROM users WHERE id = ?');
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    
                    if (!$user) {
                        http_response_code(404);
                        echo json_encode(['error' => 'User not found']);
                        exit;
                    }
                    
                    // Normalize role
                    $user['role'] = trim($user['role'] ?? '');
                    
                    echo json_encode($user);
                    break;
                    
                case 'logout':
                    if (is_logged_in()) {
                        log_audit_trail('logout', 'user', $_SESSION['user_id']);
                    }
                    // Destroy session completely on logout
                    $_SESSION = [];
                    if (ini_get("session.use_cookies")) {
                        $params = session_get_cookie_params();
                        setcookie(session_name(), '', time() - 42000,
                            $params["path"], $params["domain"],
                            $params["secure"], $params["httponly"]
                        );
                    }
                    session_destroy();
                    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("[AUTH] EXCEPTION: " . $e->getMessage());
    error_log("[AUTH] Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
?>
