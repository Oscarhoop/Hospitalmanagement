<?php
// Database initialization script
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';

$pdo = get_pdo();

try {
    // Drop existing tables if they exist
    $pdo->exec('DROP TABLE IF EXISTS audit_trail');
    $pdo->exec('DROP TABLE IF EXISTS billing');
    $pdo->exec('DROP TABLE IF EXISTS medical_records');
    $pdo->exec('DROP TABLE IF EXISTS appointments');
    $pdo->exec('DROP TABLE IF EXISTS doctors');
    $pdo->exec('DROP TABLE IF EXISTS patients');
    $pdo->exec('DROP TABLE IF EXISTS users');
    $pdo->exec('DROP TABLE IF EXISTS rooms');

    // Create users table
    $pdo->exec('
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT \'staff\',
            phone TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Create patients table
    $pdo->exec('
        CREATE TABLE patients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            dob TEXT,
            gender TEXT,
            address TEXT,
            phone TEXT,
            email TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_active INTEGER DEFAULT 1
        )
    ');

    // Create doctors table
    $pdo->exec('
        CREATE TABLE doctors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            specialty TEXT,
            phone TEXT,
            email TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Create rooms table
    $pdo->exec('
        CREATE TABLE rooms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_number TEXT NOT NULL,
            room_name TEXT,
            room_type TEXT,
            capacity INTEGER,
            notes TEXT,
            is_available INTEGER DEFAULT 1
        )
    ');

    // Create appointments table
    $pdo->exec('
        CREATE TABLE appointments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_id INTEGER NOT NULL,
            doctor_id INTEGER,
            room_id INTEGER,
            start_time DATETIME NOT NULL,
            end_time DATETIME,
            status TEXT DEFAULT \'scheduled\',
            reason TEXT,
            diagnosis TEXT,
            treatment TEXT,
            prescription TEXT,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME
        )
    ');

    // Create medical_records table
    $pdo->exec('
        CREATE TABLE medical_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_id INTEGER NOT NULL,
            appointment_id INTEGER,
            record_type TEXT NOT NULL,
            title TEXT NOT NULL,
            content TEXT,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Create billing table
    $pdo->exec('
        CREATE TABLE billing (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_id INTEGER NOT NULL,
            appointment_id INTEGER,
            amount REAL NOT NULL,
            status TEXT DEFAULT \'pending\',
            due_date TEXT,
            payment_method TEXT,
            payment_date TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME
        )
    ');

    // Create audit_trail table
    $pdo->exec('
        CREATE TABLE audit_trail (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            target_type TEXT,
            target_id INTEGER,
            details TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Insert default admin user
    $admin_email = 'admin@hospital.com';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
    $stmt->execute(['Admin User', $admin_email, $admin_password, 'admin']);

    echo "Database initialized successfully.\n";

} catch (Exception $e) {
    die("Database initialization failed: " . $e->getMessage());
}
?>