<?php
/**
 * Add sample doctors and patients to existing database
 * This script adds data without clearing existing records
 */

require_once __DIR__ . '/db.php';

function log_message($message) {
    echo "[ADD DATA] $message\n";
}

try {
    $pdo = get_pdo();
    
    // Start transaction
    $pdo->beginTransaction();
    
    log_message("Adding sample doctors and patients...");
    
    // Add sample doctors with Kenyan names
    $doctors = [
        ['Mwangi', 'Wambui', 'Cardiology', '+254 712 111 222', 'mwangi.wambui@hospital.com', 'Board certified cardiologist with 15 years experience'],
        ['Wambui', 'Kariuki', 'Pediatrics', '+254 713 222 333', 'wambui.kariuki@hospital.com', 'Pediatric specialist, expert in child development'],
        ['Kariuki', 'Wanjiku', 'Orthopedics', '+254 714 333 444', 'kariuki.wanjiku@hospital.com', 'Orthopedic surgeon specializing in sports medicine'],
        ['Wanjiku', 'Omondi', 'Dermatology', '+254 715 444 555', 'wanjiku.omondi@hospital.com', 'Dermatologist with focus on cosmetic and medical dermatology'],
        ['Omondi', 'Akinyi', 'Neurology', '+254 716 555 666', 'omondi.akinyi@hospital.com', 'Neurologist specializing in movement disorders'],
        ['Akinyi', 'Njuguna', 'Oncology', '+254 717 666 777', 'akinyi.njuguna@hospital.com', 'Oncologist with expertise in breast cancer treatment'],
        ['Njuguna', 'Wairimu', 'Emergency Medicine', '+254 718 777 888', 'njuguna.wairimu@hospital.com', 'Emergency medicine physician, trauma specialist'],
        ['Wairimu', 'Onyango', 'Psychiatry', '+254 719 888 999', 'wairimu.onyango@hospital.com', 'Psychiatrist specializing in anxiety and depression'],
        ['Onyango', 'Adhiambo', 'General Surgery', '+254 720 999 000', 'onyango.adhiambo@hospital.com', 'General surgeon with laparoscopic expertise'],
        ['Adhiambo', 'Mwangi', 'Obstetrics & Gynecology', '+254 721 000 111', 'adhiambo.mwangi@hospital.com', 'OB/GYN with focus on high-risk pregnancies']
    ];
    
    $doctorCount = 0;
    $stmt = $pdo->prepare('INSERT INTO doctors (first_name, last_name, specialty, phone, email, notes) VALUES (?, ?, ?, ?, ?, ?)');
    
    foreach ($doctors as $doctor) {
        // Check if doctor already exists by email
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM doctors WHERE email = ?');
        $checkStmt->execute([$doctor[4]]);
        if ($checkStmt->fetchColumn() == 0) {
            $stmt->execute($doctor);
            $doctorCount++;
        }
    }
    
    log_message("Added $doctorCount new doctors");
    
    // Add sample patients with Kenyan names
    $patients = [
        ['Wanjiru', 'Ochieng', '1985-05-15', 'F', '123 Ngong Road, Nairobi', '+254 712 345 678', 'wanjiru.ochieng@gmail.com', 'Regular checkup needed'],
        ['Kamau', 'Wanjala', '1990-08-22', 'M', '456 Mombasa Road, Nairobi', '+254 713 456 789', 'kamau.wanjala@gmail.com', 'Allergic to penicillin'],
        ['Njeri', 'Kamau', '1975-11-30', 'F', '789 Thika Road, Nairobi', '+254 714 567 890', 'njeri.kamau@gmail.com', 'Hypertension'],
        ['Otieno', 'Njeri', '1992-03-10', 'M', '321 Waiyaki Way, Nairobi', '+254 715 678 901', 'otieno.njeri@gmail.com', 'Diabetes type 2'],
        ['Achieng', 'Otieno', '1988-07-25', 'F', '654 Jogoo Road, Nairobi', '+254 716 789 012', 'achieng.otieno@gmail.com', ''],
        ['Kipchoge', 'Achieng', '1995-12-05', 'M', '987 Langata Road, Nairobi', '+254 717 890 123', 'kipchoge.achieng@gmail.com', 'Asthma'],
        ['Muthoni', 'Kipchoge', '1980-01-18', 'F', '147 Limuru Road, Nairobi', '+254 718 901 234', 'muthoni.kipchoge@gmail.com', 'High cholesterol'],
        ['Ochieng', 'Muthoni', '1993-09-14', 'M', '258 Kiambu Road, Nairobi', '+254 719 012 345', 'ochieng.muthoni@gmail.com', ''],
        ['Wanjala', 'Ochieng', '1978-06-20', 'M', '369 Kasarani Road, Nairobi', '+254 720 123 456', 'wanjala.ochieng@gmail.com', 'Previous surgery - knee'],
        ['Nyambura', 'Wanjala', '1987-04-08', 'F', '741 Rongai Road, Nairobi', '+254 721 234 567', 'nyambura.wanjala@gmail.com', 'Pregnant - 2nd trimester']
    ];
    
    $patientCount = 0;
    $stmt = $pdo->prepare('INSERT INTO patients (first_name, last_name, dob, gender, address, phone, email, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    
    foreach ($patients as $patient) {
        // Check if patient already exists by email
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM patients WHERE email = ?');
        $checkStmt->execute([$patient[6]]);
        if ($checkStmt->fetchColumn() == 0) {
            $stmt->execute($patient);
            $patientCount++;
        }
    }
    
    log_message("Added $patientCount new patients");
    
    // Add corresponding users for doctors (for scheduling system) with Kenyan names
    $doctorUsers = [
        ['Dr. Mwangi Wambui', 'mwangi.wambui@hospital.com', 'doctor'],
        ['Dr. Wambui Kariuki', 'wambui.kariuki@hospital.com', 'doctor'],
        ['Dr. Kariuki Wanjiku', 'kariuki.wanjiku@hospital.com', 'doctor'],
        ['Dr. Wanjiku Omondi', 'wanjiku.omondi@hospital.com', 'doctor']
    ];
    
    $userCount = 0;
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
    
    foreach ($doctorUsers as $user) {
        // Check if user already exists
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $checkStmt->execute([$user[1]]);
        if ($checkStmt->fetchColumn() == 0) {
            $stmt->execute([$user[0], $user[1], password_hash('doctor123', PASSWORD_DEFAULT), $user[2]]);
            $userCount++;
        }
    }
    
    log_message("Added $userCount new doctor users");
    
    $pdo->commit();
    
    log_message("\n✅ Successfully added sample data!");
    log_message("   - Doctors: $doctorCount");
    log_message("   - Patients: $patientCount");
    log_message("   - Doctor Users: $userCount");
    log_message("\nYou can now use these doctors and patients in the system.");
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_message("❌ Error: " . $e->getMessage());
    log_message("Stack trace: " . $e->getTraceAsString());
    exit(1);
}
?>

