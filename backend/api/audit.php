<?php
// Audit trail API and helper functions
// This function will be called from other API files to log events
// Made non-blocking so it never breaks main operations

if (!function_exists('log_audit_trail')) {
    function log_audit_trail($action, $target_type = null, $target_id = null, $details = null, $pdo = null) {
        // Wrap everything in try-catch so audit logging never breaks the main operation
        try {
            // Handle session safely
            if (session_status() == PHP_SESSION_NONE) {
                @session_start();
            }
            
            // Get user ID from session if available
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            // Get database connection
            if ($pdo === null) {
                require_once __DIR__ . '/../db.php';
                $pdo = get_pdo();
            }
            
            // Prepare details - handle if it's already a string or null
            $details_json = null;
            if ($details !== null) {
                if (is_string($details)) {
                    // Check if it's already valid JSON
                    $decoded = json_decode($details, true);
                    $details_json = (json_last_error() === JSON_ERROR_NONE) ? $details : json_encode($details);
                } else {
                    $details_json = json_encode($details);
                }
            }
            
            // Insert audit log
            $stmt = $pdo->prepare(
                'INSERT INTO audit_trail (user_id, action, target_type, target_id, details) 
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$user_id, $action, $target_type, $target_id, $details_json]);
            
        } catch (Exception $e) {
            // Silently log to error log but don't break the main operation
            error_log('Audit trail logging failed: ' . $e->getMessage());
            // Don't throw - we want audit logging to be non-blocking
        }
    }
}

// Main API logic for fetching audit trail data
if (isset($_GET['action']) && $_GET['action'] == 'get_audit_trail') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:8000'));
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    require_once __DIR__ . '/../db.php';
    
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $pdo = get_pdo();
    $stmt = $pdo->query(
       'SELECT a.id, a.action, a.target_type, a.target_id, a.details, a.timestamp, u.name as user_name 
        FROM audit_trail a 
        LEFT JOIN users u ON a.user_id = u.id 
        ORDER BY a.timestamp DESC'
    );
    $audit_trail = $stmt->fetchAll();

    echo json_encode($audit_trail);
}
?>