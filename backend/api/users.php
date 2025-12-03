<?php
// Users API endpoints - Admin only
session_start();

require_once __DIR__ . '/../cors.php';

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/audit.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = get_pdo();

// Helper functions
function read_json() {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    return $data ?: [];
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function require_admin() {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    if (!is_admin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
}

try {
    switch ($method) {
        case 'GET':
            require_admin();
            
            if (isset($_GET['id'])) {
                // Get single user
                $stmt = $pdo->prepare('SELECT id, name, email, role, phone, notes, created_at FROM users WHERE id = ?');
                $stmt->execute([$_GET['id']]);
                $user = $stmt->fetch();
                echo json_encode($user ?: null);
                exit;
            }
            
            // Get all users
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if (isset($_GET['role']) && !empty($_GET['role'])) {
                $whereClause .= " AND role = ?";
                $params[] = $_GET['role'];
            }
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $whereClause .= " AND (name LIKE ? OR email LIKE ?)";
                $search = '%' . $_GET['search'] . '%';
                $params[] = $search;
                $params[] = $search;
            }
            
            $stmt = $pdo->prepare("
                SELECT id, name, email, role, phone, notes, created_at 
                FROM users 
                $whereClause
                ORDER BY created_at DESC
            ");
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            
            echo json_encode($users);
            break;
            
        case 'POST':
            require_admin();
            
            $data = read_json();
            
            // Validate required fields
            if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name, email and password are required']);
                exit;
            }
            
            // Check if email already exists
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$data['email']]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Email already exists']);
                exit;
            }
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                INSERT INTO users (name, email, password, role, phone, notes) 
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $data['name'],
                $data['email'],
                $hashedPassword,
                $data['role'] ?? 'staff',
                $data['phone'] ?? null,
                $data['notes'] ?? null
            ]);
            
            $id = $pdo->lastInsertId();
            
            log_audit_trail('create_user', 'user', $id, $data);

            // Return created user (without password)
            $stmt = $pdo->prepare('SELECT id, name, email, role, phone, notes, created_at FROM users WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch());
            break;
            
        case 'PUT':
            require_admin();
            
            parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
            $id = $qs['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing id']);
                exit;
            }
            
            // Get current state for audit log
            $stmt = $pdo->prepare('SELECT id, name, email, role, phone, notes, created_at FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $before = $stmt->fetch();

            $data = read_json();
            
            // Build dynamic UPDATE query
            $updates = [];
            $params = [];
            
            if (isset($data['name'])) {
                $updates[] = 'name=?';
                $params[] = $data['name'];
            }
            if (isset($data['email'])) {
                // Check if email is already taken by another user
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?');
                $stmt->execute([$data['email'], $id]);
                if ($stmt->fetchColumn() > 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Email already exists']);
                    exit;
                }
                $updates[] = 'email=?';
                $params[] = $data['email'];
            }
            if (isset($data['role'])) {
                $updates[] = 'role=?';
                $params[] = $data['role'];
            }
            if (isset($data['phone'])) {
                $updates[] = 'phone=?';
                $params[] = $data['phone'];
            }
            if (isset($data['notes'])) {
                $updates[] = 'notes=?';
                $params[] = $data['notes'];
            }
            if (isset($data['password']) && !empty($data['password'])) {
                $updates[] = 'password=?';
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                exit;
            }
            
            $params[] = $id;
            
            $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id=?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Get new state for audit log
            $stmt = $pdo->prepare('SELECT id, name, email, role, phone, notes, created_at FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $after = $stmt->fetch();

            log_audit_trail('update_user', 'user', $id, ['before' => $before, 'after' => $after]);

            // Return updated user
            echo json_encode($after);
            break;
            
        case 'DELETE':
            require_admin();
            
            parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
            $id = $qs['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing id']);
                exit;
            }
            
            // Prevent deleting yourself
            if ($id == $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete your own account']);
                exit;
            }
            
            log_audit_trail('delete_user', 'user', $id);

            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
