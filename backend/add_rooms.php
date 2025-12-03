<?php
/**
 * Add sample rooms to existing database
 * This script adds rooms without clearing existing records
 */

require_once __DIR__ . '/db.php';

function log_message($message) {
    echo "[ADD ROOMS] $message\n";
}

try {
    $pdo = get_pdo();
    
    // Start transaction
    $pdo->beginTransaction();
    
    log_message("Adding sample rooms...");
    
    // Add sample rooms
    $rooms = [
        // Examination Rooms
        ['101', 'Examination Room 1', 'Examination', 2, 'Standard examination room with basic equipment', 1],
        ['102', 'Examination Room 2', 'Examination', 2, 'Standard examination room', 1],
        ['103', 'Examination Room 3', 'Examination', 1, 'Pediatric examination room', 1],
        ['104', 'Examination Room 4', 'Examination', 2, 'General examination room', 1],
        ['105', 'Examination Room 5', 'Examination', 1, 'Small examination room', 1],
        
        // Operation Rooms
        ['201', 'Operation Room 1', 'Operation', 5, 'Main operation room with full surgical equipment', 1],
        ['202', 'Operation Room 2', 'Operation', 4, 'Secondary operation room', 1],
        ['203', 'Operation Room 3', 'Operation', 3, 'Minor surgery room', 1],
        
        // Consultation Rooms
        ['301', 'Consultation Room 1', 'Consultation', 3, 'Private consultation room', 1],
        ['302', 'Consultation Room 2', 'Consultation', 3, 'Private consultation room', 1],
        ['303', 'Consultation Room 3', 'Consultation', 2, 'Small consultation room', 1],
        
        // Emergency Rooms
        ['401', 'Emergency Room 1', 'Emergency', 4, 'Emergency treatment room', 1],
        ['402', 'Emergency Room 2', 'Emergency', 3, 'Emergency treatment room', 1],
        
        // ICU Rooms
        ['501', 'ICU Room 1', 'ICU', 2, 'Intensive care unit room', 1],
        ['502', 'ICU Room 2', 'ICU', 2, 'Intensive care unit room', 1],
        ['503', 'ICU Room 3', 'ICU', 1, 'Single patient ICU room', 1],
        
        // Ward Rooms
        ['601', 'Ward Room 1', 'Ward', 4, 'General ward room with 4 beds', 1],
        ['602', 'Ward Room 2', 'Ward', 4, 'General ward room with 4 beds', 1],
        ['603', 'Ward Room 3', 'Ward', 2, 'Semi-private ward room', 1],
        ['604', 'Ward Room 4', 'Ward', 1, 'Private ward room', 1],
        
        // Specialized Rooms
        ['701', 'X-Ray Room', 'Imaging', 2, 'Radiology and X-ray facility', 1],
        ['702', 'Ultrasound Room', 'Imaging', 2, 'Ultrasound examination room', 1],
        ['703', 'Laboratory', 'Lab', 3, 'Medical laboratory', 1],
        ['704', 'Pharmacy', 'Pharmacy', 2, 'Pharmacy and medication dispensing', 1],
        ['705', 'Physical Therapy Room', 'Therapy', 3, 'Physical therapy and rehabilitation', 1],
        
        // Administrative
        ['801', 'Reception Area', 'Administrative', 10, 'Main reception and waiting area', 1],
        ['802', 'Nurses Station', 'Administrative', 5, 'Nurses station and monitoring', 1],
        ['803', 'Conference Room', 'Administrative', 20, 'Staff meeting and conference room', 1]
    ];
    
    $roomCount = 0;
    $stmt = $pdo->prepare('INSERT INTO rooms (room_number, room_name, room_type, capacity, notes, is_available) VALUES (?, ?, ?, ?, ?, ?)');
    
    foreach ($rooms as $room) {
        // Check if room already exists by room_number
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE room_number = ?');
        $checkStmt->execute([$room[0]]);
        if ($checkStmt->fetchColumn() == 0) {
            $stmt->execute($room);
            $roomCount++;
        }
    }
    
    $pdo->commit();
    
    log_message("\n✅ Successfully added rooms!");
    log_message("   - Total rooms added: $roomCount");
    log_message("   - Examination Rooms: 5");
    log_message("   - Operation Rooms: 3");
    log_message("   - Consultation Rooms: 3");
    log_message("   - Emergency Rooms: 2");
    log_message("   - ICU Rooms: 3");
    log_message("   - Ward Rooms: 4");
    log_message("   - Specialized Rooms: 5");
    log_message("   - Administrative Rooms: 3");
    log_message("\nYou can now view and manage these rooms in the Rooms section.");
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_message("❌ Error: " . $e->getMessage());
    log_message("Stack trace: " . $e->getTraceAsString());
    exit(1);
}
?>

