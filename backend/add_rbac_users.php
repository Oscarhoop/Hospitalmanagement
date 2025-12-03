<?php
/**
 * Add RBAC users with proper roles and permissions
 * Creates nurses, receptionists, and billing officers
 */

require_once __DIR__ . '/db.php';

function log_message($message) {
    echo "[RBAC USERS] $message\n";
}

try {
    $pdo = get_pdo();
    
    // Start transaction
    $pdo->beginTransaction();
    
    log_message("Creating RBAC users with proper roles...");
    
    // Define users with their roles
    $users = [
        // Nurses
        [
            'name' => 'Nurse Sarah Johnson',
            'email' => 'nurse.sarah@hospital.com',
            'password' => 'nurse123',
            'role' => 'nurse',
            'phone' => '555-0301',
            'notes' => 'Senior nurse, specializes in emergency care'
        ],
        [
            'name' => 'Nurse Mark Davis',
            'email' => 'nurse.mark@hospital.com',
            'password' => 'nurse123',
            'role' => 'nurse',
            'phone' => '555-0302',
            'notes' => 'Pediatric nurse'
        ],
        [
            'name' => 'Nurse Emily Chen',
            'email' => 'nurse.emily@hospital.com',
            'password' => 'nurse123',
            'role' => 'nurse',
            'phone' => '555-0303',
            'notes' => 'ICU nurse'
        ],
        
        // Receptionists
        [
            'name' => 'Receptionist Lisa Brown',
            'email' => 'reception.lisa@hospital.com',
            'password' => 'reception123',
            'role' => 'receptionist',
            'phone' => '555-0401',
            'notes' => 'Front desk receptionist'
        ],
        [
            'name' => 'Receptionist James Wilson',
            'email' => 'reception.james@hospital.com',
            'password' => 'reception123',
            'role' => 'receptionist',
            'phone' => '555-0402',
            'notes' => 'Appointment scheduling specialist'
        ],
        
        // Billing Officers
        [
            'name' => 'Billing Officer Michael Taylor',
            'email' => 'billing.michael@hospital.com',
            'password' => 'billing123',
            'role' => 'billing',
            'phone' => '555-0501',
            'notes' => 'Senior billing officer'
        ],
        [
            'name' => 'Billing Officer Jennifer Martinez',
            'email' => 'billing.jennifer@hospital.com',
            'password' => 'billing123',
            'role' => 'billing',
            'phone' => '555-0502',
            'notes' => 'Insurance specialist'
        ],
        
        // Additional roles (if needed)
        [
            'name' => 'Pharmacist David Lee',
            'email' => 'pharmacist.david@hospital.com',
            'password' => 'pharmacy123',
            'role' => 'pharmacist',
            'phone' => '555-0601',
            'notes' => 'Licensed pharmacist'
        ],
        [
            'name' => 'Lab Technician Anna White',
            'email' => 'lab.anna@hospital.com',
            'password' => 'lab123',
            'role' => 'lab_tech',
            'phone' => '555-0701',
            'notes' => 'Medical laboratory technician'
        ]
    ];
    
    $created = 0;
    $skipped = 0;
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, phone, notes) VALUES (?, ?, ?, ?, ?, ?)');
    
    foreach ($users as $user) {
        // Check if user already exists
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $checkStmt->execute([$user['email']]);
        
        if ($checkStmt->fetchColumn() == 0) {
            $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
            $stmt->execute([
                $user['name'],
                $user['email'],
                $hashedPassword,
                $user['role'],
                $user['phone'],
                $user['notes']
            ]);
            $created++;
            log_message("✓ Created {$user['role']}: {$user['email']}");
        } else {
            $skipped++;
            log_message("- Skipped (exists): {$user['email']}");
        }
    }
    
    $pdo->commit();
    
    log_message("\n✅ User creation completed!");
    log_message("   Created: $created users");
    log_message("   Skipped: $skipped users (already exist)");
    
    log_message("\n📋 User Credentials:");
    log_message("\n👩‍⚕️ NURSES (Can view patients, record vitals, update medical records):");
    log_message("   - nurse.sarah@hospital.com / nurse123");
    log_message("   - nurse.mark@hospital.com / nurse123");
    log_message("   - nurse.emily@hospital.com / nurse123");
    
    log_message("\n📋 RECEPTIONISTS (Can register patients, schedule appointments, create bills):");
    log_message("   - reception.lisa@hospital.com / reception123");
    log_message("   - reception.james@hospital.com / reception123");
    
    log_message("\n💰 BILLING OFFICERS (Can manage billing, view financial reports):");
    log_message("   - billing.michael@hospital.com / billing123");
    log_message("   - billing.jennifer@hospital.com / billing123");
    
    log_message("\n💊 PHARMACIST:");
    log_message("   - pharmacist.david@hospital.com / pharmacy123");
    
    log_message("\n🔬 LAB TECHNICIAN:");
    log_message("   - lab.anna@hospital.com / lab123");
    
    log_message("\n🔐 Permissions are automatically enforced based on role!");
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_message("❌ Error: " . $e->getMessage());
    log_message("Stack trace: " . $e->getTraceAsString());
    exit(1);
}
?>

