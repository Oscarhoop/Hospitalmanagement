<?php
// Enhanced Patients API endpoints
// Set secure session cookie parameters BEFORE starting session
$isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] == 443);
$isLocalhost = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $isSecure ? '1' : '0');
ini_set('session.cookie_samesite', $isLocalhost ? 'Lax' : 'Strict');

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => $isLocalhost ? 'Lax' : 'Strict'
]);

session_start([
    'cookie_lifetime' => 86400,
    'use_strict_mode' => true,
    'use_only_cookies' => 1
]);

require_once __DIR__ . '/../error_handler.php';

require_once __DIR__ . '/../cors.php';

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/permissions.php';
$pdo = get_pdo();

$method = $_SERVER['REQUEST_METHOD'];

// Helper functions
function read_json() {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    return $data ?: [];
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

try {
    if ($method === 'GET') {
        // Check if user can access patients
        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        if (!can_access_patients()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Patient access required.']);
            exit;
        }
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            $row = $stmt->fetch();
            echo json_encode($row ?: null);
            exit;
        }
        
        // Get all patients with optional filtering and search
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $whereClause .= " AND (first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $searchTerm = '%' . $_GET['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (isset($_GET['gender']) && !empty($_GET['gender'])) {
            $whereClause .= " AND gender = ?";
            $params[] = $_GET['gender'];
        }
        
        $orderBy = "ORDER BY created_at DESC";
        if (isset($_GET['sort'])) {
            $sortField = $_GET['sort'];
            $sortOrder = $_GET['order'] ?? 'ASC';
            if (in_array($sortField, ['first_name', 'last_name', 'dob', 'created_at'])) {
                $orderBy = "ORDER BY $sortField $sortOrder";
            }
        }
        
        $stmt = $pdo->prepare("SELECT * FROM patients $whereClause $orderBy");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        echo json_encode($rows);
        exit;
    }

    if ($method === 'POST') {
        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        if (!can_create_patients()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. You do not have permission to create patients.']);
            exit;
        }
        
        $data = read_json();
        $stmt = $pdo->prepare('
            INSERT INTO patients (
                first_name, last_name, dob, gender, address, phone, email, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['dob'] ?? null,
            $data['gender'] ?? null,
            $data['address'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['notes'] ?? null
        ]);
        $id = $pdo->lastInsertId();
        log_audit_trail('create_patient', 'patient', $id, $data);
        $stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode($stmt->fetch());
        exit;
    }

    if ($method === 'PUT') {
        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        if (!can_access_patients()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. You do not have permission to edit patients.']);
            exit;
        }
        
        parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
        $id = $qs['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            exit;
        }
        
        // Get current state for audit log
        $stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
        $stmt->execute([$id]);
        $before = $stmt->fetch();

        $data = read_json();
        $stmt = $pdo->prepare('
            UPDATE patients SET 
                first_name=?, last_name=?, dob=?, gender=?, address=?, phone=?, email=?, notes=?
            WHERE id=?
        ');
        $stmt->execute([
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['dob'] ?? null,
            $data['gender'] ?? null,
            $data['address'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['notes'] ?? null,
            $id
        ]);

        // Get new state for audit log
        $stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
        $stmt->execute([$id]);
        $after = $stmt->fetch();

        log_audit_trail('update_patient', 'patient', $id, ['before' => $before, 'after' => $after]);

        echo json_encode($after);
        exit;
    }

    if ($method === 'DELETE') {
        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        // Only admin and receptionist can delete patients
        require_role(['admin', 'receptionist']);
        
        parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
        $id = $qs['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            exit;
        }
        log_audit_trail('delete_patient', 'patient', $id);
        // Soft delete - mark as inactive (if is_active column exists) or hard delete
        try {
            $stmt = $pdo->prepare('UPDATE patients SET is_active = 0 WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Patient deactivated successfully']);
        } catch (Exception $e) {
            // If is_active column doesn't exist, do hard delete
            $stmt = $pdo->prepare('DELETE FROM patients WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Patient deleted successfully']);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
