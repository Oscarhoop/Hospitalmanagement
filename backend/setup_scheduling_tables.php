<?php
/**
 * Script to set up scheduling-related database tables
 */

require_once __DIR__ . '/db.php';

function log_message($message) {
    echo "[SETUP] $message\n";
}

try {
    $pdo = get_pdo();
    
    // Enable foreign key constraints
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    // Begin transaction
    $pdo->beginTransaction();
    
    log_message("Creating shift_templates table...");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shift_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            color TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    log_message("Creating staff_schedules table...");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS staff_schedules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            schedule_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            status TEXT NOT NULL DEFAULT 'scheduled',
            notes TEXT,
            shift_template_id INTEGER,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (shift_template_id) REFERENCES shift_templates(id) ON DELETE SET NULL
        )
    ");
    
    log_message("Creating leave_requests table...");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS leave_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            leave_type TEXT NOT NULL,
            reason TEXT,
            status TEXT NOT NULL DEFAULT 'pending',
            approved_by INTEGER,
            approved_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    
    log_message("Creating shift_swaps table...");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shift_swaps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            requesting_user_id INTEGER NOT NULL,
            target_user_id INTEGER NOT NULL,
            schedule_id INTEGER NOT NULL,
            reason TEXT,
            status TEXT NOT NULL DEFAULT 'pending',
            approved_by INTEGER,
            approved_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (requesting_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (schedule_id) REFERENCES staff_schedules(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    
    log_message("Creating audit_trail table...");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_trail (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            action TEXT NOT NULL,
            type TEXT NOT NULL,
            record_id INTEGER,
            data TEXT,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    
    // Add sample shift templates if none exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM shift_templates");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        log_message("Adding sample shift templates...");
        $templates = [
            ['Morning Shift', '08:00:00', '16:00:00', '#4CAF50'],
            ['Afternoon Shift', '14:00:00', '22:00:00', '#2196F3'],
            ['Night Shift', '22:00:00', '06:00:00', '#9C27B0'],
            ['Weekend Shift', '09:00:00', '17:00:00', '#FF9800']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO shift_templates (name, start_time, end_time, color) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($templates as $template) {
            $stmt->execute($template);
        }
        
        log_message("Added " . count($templates) . " sample shift templates");
    }
    
    // Commit the transaction
    $pdo->commit();
    
    log_message("Scheduling tables created successfully!");
    
} catch (PDOException $e) {
    // Rollback the transaction if something failed
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    log_message("Error: " . $e->getMessage());
    log_message("Stack trace: " . $e->getTraceAsString());
    exit(1);
}
?>

<h2>Scheduling Tables Setup Complete</h2>
<p>The scheduling database tables have been created successfully.</p>
<p>You can now use the scheduling features of the application.</p>
