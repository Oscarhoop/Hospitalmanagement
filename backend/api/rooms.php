<?php
// Rooms API endpoints
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

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single room
                log_audit_trail('view_room', 'room', $_GET['id'], ['filters' => $_GET]);
                $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = ?');
                $stmt->execute([$_GET['id']]);
                $room = $stmt->fetch();
                echo json_encode($room ?: null);
                exit;
            }
            
            // Get all rooms with optional filtering
            log_audit_trail('list_rooms', 'room', null, ['filters' => $_GET]);
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if (isset($_GET['room_type']) && !empty($_GET['room_type'])) {
                $whereClause .= " AND room_type = ?";
                $params[] = $_GET['room_type'];
            }
            
            if (isset($_GET['is_available']) && $_GET['is_available'] !== '') {
                $whereClause .= " AND is_available = ?";
                $params[] = $_GET['is_available'];
            }
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $whereClause .= " AND (room_number LIKE ? OR room_name LIKE ?)";
                $searchTerm = '%' . $_GET['search'] . '%';
                $params = array_merge($params, [$searchTerm, $searchTerm]);
            }
            
            $stmt = $pdo->prepare("SELECT * FROM rooms $whereClause ORDER BY room_number");
            $stmt->execute($params);
            $rooms = $stmt->fetchAll();
            
            echo json_encode($rooms);
            break;
            
        case 'POST':
            if (!is_logged_in()) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                exit;
            }
            
            $data = read_json();
            
            // Validate required fields
            if (empty($data['room_number']) || empty($data['room_name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Room number and name are required']);
                exit;
            }
            
            $stmt = $pdo->prepare('
                INSERT INTO rooms (room_number, room_name, room_type, capacity, is_available, notes) 
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $data['room_number'],
                $data['room_name'],
                $data['room_type'] ?? 'general',
                $data['capacity'] ?? 1,
                isset($data['is_available']) ? (int)$data['is_available'] : 1,
                $data['notes'] ?? null
            ]);
            
            $id = $pdo->lastInsertId();
            $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch());
            log_audit_trail('create_room', 'room', $id, $data);
            break;
            
        case 'PUT':
            if (!is_logged_in()) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
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
            $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = ?');
            $stmt->execute([$id]);
            $before = $stmt->fetch();
            
            if (!$before) {
                http_response_code(404);
                echo json_encode(['error' => 'Room not found']);
                exit;
            }

            $data = read_json();
            $stmt = $pdo->prepare('
                UPDATE rooms SET 
                    room_number=?, room_name=?, room_type=?, capacity=?, is_available=?, notes=?
                WHERE id=?
            ');
            $stmt->execute([
                $data['room_number'] ?? '',
                $data['room_name'] ?? '',
                $data['room_type'] ?? 'general',
                $data['capacity'] ?? 1,
                isset($data['is_available']) ? (int)$data['is_available'] : 1,
                $data['notes'] ?? null,
                $id
            ]);
            
            $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = ?');
            $stmt->execute([$id]);
            $after = $stmt->fetch();
            log_audit_trail('update_room', 'room', $id, ['before' => $before, 'after' => $after]);
            echo json_encode($after);
            break;
            
        case 'DELETE':
            if (!is_logged_in()) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                exit;
            }
            
            parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
            $id = $qs['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing id']);
                exit;
            }
            
            log_audit_trail('delete_room', 'room', $id);
            $stmt = $pdo->prepare('DELETE FROM rooms WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
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
