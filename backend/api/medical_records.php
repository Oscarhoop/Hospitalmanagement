<?php
// Medical Records API endpoints
session_start();

require_once __DIR__ . '/../cors.php';

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/permissions.php';

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
            if (!is_logged_in()) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                exit;
            }
            if (!can_access_medical_records()) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied. Medical records access required.']);
                exit;
            }
            
            if (isset($_GET['id'])) {
                // Get single medical record
                $stmt = $pdo->prepare('
                    SELECT mr.*, 
                           p.first_name as patient_first_name, p.last_name as patient_last_name,
                           u.name as created_by_name
                    FROM medical_records mr
                    LEFT JOIN patients p ON mr.patient_id = p.id
                    LEFT JOIN users u ON mr.created_by = u.id
                    WHERE mr.id = ?
                ');
                $stmt->execute([$_GET['id']]);
                $record = $stmt->fetch();
                echo json_encode($record ?: null);
                exit;
            }
            
            // Get all medical records with filtering
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if (isset($_GET['patient_id']) && !empty($_GET['patient_id'])) {
                $whereClause .= " AND mr.patient_id = ?";
                $params[] = $_GET['patient_id'];
            }
            
            if (isset($_GET['appointment_id']) && !empty($_GET['appointment_id'])) {
                $whereClause .= " AND mr.appointment_id = ?";
                $params[] = $_GET['appointment_id'];
            }
            
            if (isset($_GET['record_type']) && !empty($_GET['record_type'])) {
                $whereClause .= " AND mr.record_type = ?";
                $params[] = $_GET['record_type'];
            }
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $whereClause .= " AND (mr.title LIKE ? OR mr.content LIKE ?)";
                $searchTerm = '%' . $_GET['search'] . '%';
                $params = array_merge($params, [$searchTerm, $searchTerm]);
            }
            
            $orderBy = "ORDER BY mr.created_at DESC";
            if (isset($_GET['sort'])) {
                $sortField = $_GET['sort'];
                $sortOrder = $_GET['order'] ?? 'DESC';
                if (in_array($sortField, ['title', 'record_type', 'created_at'])) {
                    $orderBy = "ORDER BY mr.$sortField $sortOrder";
                }
            }
            
            $stmt = $pdo->prepare("
                SELECT mr.*, 
                       p.first_name as patient_first_name, p.last_name as patient_last_name,
                       u.name as created_by_name
                FROM medical_records mr
                LEFT JOIN patients p ON mr.patient_id = p.id
                LEFT JOIN users u ON mr.created_by = u.id
                $whereClause
                $orderBy
            ");
            $stmt->execute($params);
            $records = $stmt->fetchAll();
            
            echo json_encode($records);
            break;
            
        case 'POST':
            if (!is_logged_in()) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                exit;
            }
            
            if (!can_access_medical_records()) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied. You do not have permission to create medical records.']);
                exit;
            }
            
            $data = read_json();
            
            // Validate required fields
            if (empty($data['patient_id']) || empty($data['record_type']) || empty($data['title'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Patient ID, record type, and title are required']);
                exit;
            }
            
            $stmt = $pdo->prepare('
                INSERT INTO medical_records (
                    patient_id, appointment_id, record_type, title, content, created_by
                ) VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $data['patient_id'],
                $data['appointment_id'] ?? null,
                $data['record_type'],
                $data['title'],
                $data['content'] ?? '',
                $_SESSION['user_id']
            ]);
            
            $id = $pdo->lastInsertId();
            
            log_audit_trail('create_medical_record', 'medical_record', $id, $data);
            
            // Return record with patient details
            $stmt = $pdo->prepare('
                SELECT mr.*, 
                       p.first_name as patient_first_name, p.last_name as patient_last_name,
                       u.name as created_by_name
                FROM medical_records mr
                LEFT JOIN patients p ON mr.patient_id = p.id
                LEFT JOIN users u ON mr.created_by = u.id
                WHERE mr.id = ?
            ');
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch());
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
            $stmt = $pdo->prepare('SELECT * FROM medical_records WHERE id = ?');
            $stmt->execute([$id]);
            $before = $stmt->fetch();

            $data = read_json();
            
            $updateFields = [];
            $params = [];
            
            if (isset($data['record_type'])) {
                $updateFields[] = 'record_type = ?';
                $params[] = $data['record_type'];
            }
            
            if (isset($data['title'])) {
                $updateFields[] = 'title = ?';
                $params[] = $data['title'];
            }
            
            if (isset($data['content'])) {
                $updateFields[] = 'content = ?';
                $params[] = $data['content'];
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                exit;
            }
            
            $params[] = $id;
            $sql = 'UPDATE medical_records SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Get new state for audit log
            $stmt = $pdo->prepare('SELECT * FROM medical_records WHERE id = ?');
            $stmt->execute([$id]);
            $after = $stmt->fetch();

            log_audit_trail('update_medical_record', 'medical_record', $id, ['before' => $before, 'after' => $after]);

            // Return updated record
            $stmt = $pdo->prepare('
                SELECT mr.*, 
                       p.first_name as patient_first_name, p.last_name as patient_last_name,
                       u.name as created_by_name
                FROM medical_records mr
                LEFT JOIN patients p ON mr.patient_id = p.id
                LEFT JOIN users u ON mr.created_by = u.id
                WHERE mr.id = ?
            ');
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch());
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
            
            log_audit_trail('delete_medical_record', 'medical_record', $id);

            // Delete record
            $stmt = $pdo->prepare('DELETE FROM medical_records WHERE id = ?');
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Medical record deleted successfully']);
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
