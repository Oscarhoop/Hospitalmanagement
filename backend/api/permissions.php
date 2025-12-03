<?php
/**
 * Permission checking functions for RBAC
 */

function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}

function is_admin() {
    return get_user_role() === 'admin';
}

function is_receptionist() {
    return get_user_role() === 'receptionist';
}

function is_doctor() {
    return get_user_role() === 'doctor';
}

function is_nurse() {
    return get_user_role() === 'nurse';
}

function is_billing() {
    return get_user_role() === 'billing';
}

function is_pharmacist() {
    return get_user_role() === 'pharmacist';
}

function is_lab_tech() {
    return get_user_role() === 'lab_tech';
}

function require_role($allowed_roles) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $user_role = get_user_role();
    if (!in_array($user_role, $allowed_roles)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Required role: ' . implode(' or ', $allowed_roles)]);
        exit;
    }
}

function can_access_billing() {
    $role = get_user_role();
    return in_array($role, ['admin', 'receptionist', 'billing']);
}

function can_access_medical_records() {
    $role = get_user_role();
    return in_array($role, ['admin', 'doctor', 'nurse']);
}

function can_access_patients() {
    $role = get_user_role();
    return in_array($role, ['admin', 'receptionist', 'doctor', 'nurse', 'billing', 'pharmacist', 'lab_tech']);
}

function can_create_patients() {
    $role = get_user_role();
    return in_array($role, ['admin', 'receptionist']);
}

function can_create_appointments() {
    $role = get_user_role();
    return in_array($role, ['admin', 'receptionist']);
}

function can_create_billing() {
    $role = get_user_role();
    return in_array($role, ['admin', 'receptionist', 'billing']);
}

function can_edit_billing() {
    $role = get_user_role();
    return in_array($role, ['admin', 'billing']);
}

function can_view_reports() {
    $role = get_user_role();
    return in_array($role, ['admin', 'billing', 'doctor']);
}

function can_manage_users() {
    return is_admin();
}

