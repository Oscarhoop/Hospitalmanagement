# Role-Based Access Control (RBAC) Implementation

## Overview

This document describes the complete Role-Based Access Control system implemented in the Patient Management System. The system supports 7 different roles with fine-grained permissions for security and workflow optimization.

---

## Installation & Setup 

### Step 1: Run Database Migration

```bash
cd C:\xampp\htdocs\Hospital-management\backend
php init_db.php
```
**This creates:**

- New user table columns (department, specialization, employee_id)
- Audit log table
- Patient assignments table
- Prescriptions table
- Lab tests table
- Vital signs table
- All necessary indexes 

### Step 2: Create Sample Users

```bash
php add_rbac_users.php
```
**This creates 7 test users:**

- admin@hospital.com / admin123
- reception@hospital.com / reception123
- doctor@hospital.com / doctor123
- nurse@hospital.com / nurse123
- pharmacy@hospital.com / pharmacy123
- lab@hospital.com / lab123
- billing@hospital.com / billing123 

### Step 3: Refresh Browser

Clear browser cache (Ctrl+Shift+Delete) and refresh the application.

---

## User Roles

### 1. Administrator

**Full system access**


- All permissions
- User management
- System configuration
- Complete audit trail access 

### 

2. Receptionist **Front desk & patient registration**


- Register new patients
- Schedule appointments
- Process basic billing
- View doctor schedules
- Cannot see medical records
- Cannot access reports 

### 

3. Doctor **Medical care & treatment**


- View assigned patients only
- Add diagnoses & treatment plans
- Create prescriptions
- Order lab tests
- Update appointment notes
- Cannot register new patients
- Cannot see billing
- Cannot see other doctors' patients 

### 

4. Nurse **Patient care & monitoring**


- Record vital signs
- View assigned patients
- View prescriptions
- Update appointment notes
- Cannot create prescriptions
- Cannot add diagnoses
- Cannot access billing 

### 

5. Pharmacist **Medication management**


- View all prescriptions
- Dispense medications
- View patient allergies (safety)
- Cannot see full medical records
- Cannot schedule appointments 

### 

6. Lab Technician **Laboratory testing**


- View lab test orders
- Upload test results
- Mark tests as complete
- Cannot see prescriptions
- Cannot access billing 

### 

7. Billing Officer **Financial management**


- Create & edit bills
- Process payments
- Generate invoices
- Financial reports
- Cannot see medical records - Cannot schedule appointments ---

## Permission Matrix 

| Feature 

| Admin 

| Receptionist 

| Doctor 

| Nurse 

| Pharmacist 

| Lab Tech 

| Billing 

| 

|---

----

--

|:---

--:

|:---

----

----

-:

|:---

---:

|:---

--:

|:---

----

---:

|:---

----

-:

|:---

----

:

| 

| **Dashboard**
 

| All 

| Basic 

| Personal 

| Ward 

| Pharmacy 

| Lab 

| Financial 

| 

| **Add Patients**
 

| 

| 

| 

| 

| 

| 

| 

| 

| **View All Patients**
 

| 

| 

| 

| 

| 

| 

| Limited 

| 

| **View Assigned Patients**
 

| 

| 

| 

| 

| Limited 

| Limited 

| 

| 

| **Medical Records**
 

| Full 

| 

| Full 

| Limited 

| 

| 

| 

| 

| **Create Prescriptions**
 

| 

| 

| 

| 

| 

| 

| 

| 

| **Dispense Medications**
 

| 

| 

| 

| 

| 

| 

| 

| 

| **Order Lab Tests**
 

| 

| 

| 

| 

| 

| 

| 

| 

| **Upload Lab Results**
 

| 

| 

| 

| 

| 

| 

| 

| 

| **Record Vital Signs**
 

| 

| 

| 

| 

| 

| 

| 

| 

| **Create Bills**
 

| 

| 

| 

| 

| 

| 

| 

| 

| **Financial Reports**
 

| 

| 

| 

| 

| 

| 

| 

| 

| **User Management**
 

| 

| 

| 

| 

| 

| 

| 

| ---

## Workflow Examples 

### Patient Visit Flow

```
1. RECEPTIONIST → Registers patient, schedules appointment ↓ 
2. NURSE → Records vital signs (BP, temperature, weight) ↓ 
3. DOCTOR → Reviews vitals, examines patient → Adds diagnosis → Creates prescription → Orders lab tests ↓ 
4. LAB TECH → Receives order, performs test, uploads results ↓ 
5. DOCTOR → Reviews results, updates treatment plan ↓ 
6. PHARMACIST → Receives prescription, dispenses medication ↓ 
7. BILLING → Generates bill, processes payment ↓ 
8. RECEPTIONIST → Patient checks out

```
### Emergency Scenario

```
NURSE → Records emergency vital signs → Notifies doctor DOCTOR → Views vitals immediately → Makes emergency diagnosis → Orders urgent tests LAB TECH → Priority processing → Uploads results stat DOCTOR → Reviews, treats patient

```

---

## UI Adaptations by Role 

### Navigation Menu **Admin sees:**

```
Dashboard 

| Patients 

| Appointments 

| Doctors 

| Medical Records 

| Prescriptions 

| Lab Tests 

| Vital Signs 

| Billing 

| Reports 

| Users

```
**
Receptionist sees:**

```
Dashboard 

| Patients 

| Appointments 

| Doctors 

| Billing

```
**
Doctor sees:**

```
Dashboard 

| My Appointments 

| My Patients 

| Medical Records 

| Prescriptions 

| Lab Tests

```
**
Nurse sees:**

```
Dashboard 

| Today's Appointments 

| My Patients 

| Vital Signs 

| Medications

```
**
Pharmacist sees:**

```
Dashboard 

| Prescriptions 

| Dispensing Queue

```
**
Lab Tech sees:**

```
Dashboard 

| Lab Tests 

| Pending Tests 

| Results

```
**
Billing sees:**

```
Dashboard 

| Billing 

| Payments 

| Reports

```
### User Badge Display Each role has a colored badge displayed next to their name: 

- **
Admin** - Red (#ef4444) 
- **
Receptionist** - Blue (#3b82f6) 
- **
Doctor** - Green (#10b981) 
- **
Nurse** - Purple (#8b5cf6) 
- **
Pharmacist** - Orange (#f59e0b) 
- **
Lab Tech** - Cyan (#06b6d4) 
- **
Billing** - Pink (#ec4899) ---

## Database Schema 

### New Tables 

#### audit_log Tracks all user actions for security and compliance.

```
sql
- id (PRIMARY KEY)
- user_id (FOREIGN KEY → users.id)
- action (VARCHAR: create, read, update, delete)
- table_name (VARCHAR)
- record_id (INTEGER)
- old_values (TEXT: JSON)
- new_values (TEXT: JSON)
- ip_address (VARCHAR)
- user_agent (TEXT)
- created_at (DATETIME)

```
#### patient_assignments Links nurses/doctors to specific patients.

```
sql
- id (PRIMARY KEY)
- patient_id (FOREIGN KEY → patients.id)
- assigned_to (FOREIGN KEY → users.id)
- assigned_by (FOREIGN KEY → users.id)
- assignment_type (VARCHAR: primary, temporary, specialist)
- notes (TEXT)
- assigned_at (DATETIME)
- expires_at (DATETIME)

```
#### prescriptions Tracks medications prescribed and dispensed.

```
sql
- id (PRIMARY KEY)
- patient_id (FOREIGN KEY)
- appointment_id (FOREIGN KEY)
- prescribed_by (FOREIGN KEY → users.id)
- medication_name (VARCHAR)
- dosage (VARCHAR)
- frequency (VARCHAR)
- duration (VARCHAR)
- quantity (INTEGER)
- instructions (TEXT)
- status (VARCHAR: pending, dispensed, cancelled)
- dispensed_by (FOREIGN KEY → users.id)
- dispensed_at (DATETIME)
- created_at (DATETIME)

```
#### lab_tests Laboratory test orders and results.

```
sql
- id (PRIMARY KEY)
- patient_id (FOREIGN KEY)
- appointment_id (FOREIGN KEY)
- ordered_by (FOREIGN KEY → users.id)
- test_name (VARCHAR)
- test_type (VARCHAR)
- status (VARCHAR: pending, in_progress, completed, cancelled)
- priority (VARCHAR: routine, urgent, stat)
- sample_collected_at (DATETIME)
- result (TEXT)
- result_file (VARCHAR: path to PDF/image)
- performed_by (FOREIGN KEY → users.id)
- verified_by (FOREIGN KEY → users.id)
- created_at (DATETIME)
- completed_at (DATETIME)

```
#### vital_signs Patient vital signs recorded by nurses.

```
sql
- id (PRIMARY KEY)
- patient_id (FOREIGN KEY)
- appointment_id (FOREIGN KEY)
- recorded_by (FOREIGN KEY → users.id)
- blood_pressure_systolic (INTEGER)
- blood_pressure_diastolic (INTEGER)
- heart_rate (INTEGER)
- temperature (DECIMAL)
- respiratory_rate (INTEGER)
- oxygen_saturation (INTEGER)
- weight (DECIMAL)
- height (DECIMAL)
- bmi (DECIMAL)
- notes (TEXT)
- recorded_at (DATETIME)

```
### Updated Tables 

#### users

```
sql + department (VARCHAR: Administration, Medical, Nursing, etc.) + specialization (VARCHAR: Cardiology, Pediatrics, etc.) + employee_id (VARCHAR UNIQUE: EMP001, EMP002, etc.)

```
#### doctors

```
sql + user_id (INTEGER UNIQUE: links to users table)

```

---

## JavaScript API 

### RBAC Object

```
javascript // Check permission RBAC.hasPermission('doctor', 'prescriptions', 'create') // Returns: true // Get access level RBAC.getAccessLevel('nurse', 'patients') // Returns: { view: 'assigned', add: false, edit: false, delete: false } // Get navigation items RBAC.getNavigation('receptionist') // Returns: ['dashboard', 'patients', 'appointments', 'doctors', 'billing'] // Get role info RBAC.getRoleInfo('doctor') // Returns: { label: 'Doctor', color: '#10b981', icon: ' ', ... } // Check if can view patient RBAC.canViewPatient('doctor', 123, [123, 456, 789]) // Returns: true (if patient 123 is in assigned list)

```
### Global Helper Functions

```
javascript // Check permission for current user hasPermission('patients', 'add') // Get access level for current user getAccessLevel('medical_records') // Check if current user can view patient canViewPatient(patientId)

```

---

## Security Features 

### 

1. Session-Based Authentication
- User must be logged in to access any data
- Session expires after inactivity - Automatic logout on session expiration 

### 

2. Action-Level Permissions
- Each action (view, add, edit, delete) checked separately
- Prevents unauthorized modifications 

### 

3. Data Filtering
- Doctors see only their assigned patients
- Nurses see only their assigned patients
- Pharmacists see only prescriptions (no full records) 

### 

4. Audit Trail
- Every action logged with:
- Who did it (user_id)
- What they did (action type)
- When they did it (timestamp)
- What changed (old/new values) - Where from (IP address) 

### 

5. UI-Level Protection
- Hidden buttons for unauthorized actions
- Disabled forms for read-only access - Navigation menu filtered by role ---

## Testing the System 

### Test Each Role: 

1. **
Login as each user** 
2. **
Verify navigation**
- Should only see allowed sections 
3. **
Try forbidden actions**
- Should be blocked 
4. **
Check data visibility** - Should only see permitted data 

### Test Scenarios: 

#### Scenario 1: Doctor Cannot See Billing 

1. Login as doctor@hospital.com 
2. Navigation should NOT have "Billing" link 
3. Directly navigate to billing (if possible) 
4. Should be redirected or see "Access Denied" 

#### Scenario 2: Receptionist Cannot Add Medical Records 

1. Login as reception@hospital.com 
2. Open a patient 
3. Medical Records section should not be visible 
4. Cannot add diagnosis or treatment notes 

#### Scenario 3: Nurse Can Record Vitals 

1. Login as nurse@hospital.com 
2. View assigned patient 
3. Can record vital signs 
4. Cannot create prescriptions ---

## Future Enhancements 

### Phase 3 (Recommended): 

1. **
Patient Portal** - Let patients view their own records 
2. **
Two-Factor Authentication**
- Extra security for admins 
3. **
IP Whitelisting**
- Restrict access by location 
4. **
Session Monitoring**
- Track active sessions 
5. **
Permission Groups**
- Create custom role combinations 
6. **
Temporary Access** - Grant time-limited permissions 
7. **
Audit Reports** - Generate compliance reports ---

## Troubleshooting 

### Problem: "Access Denied" for admin **

Solution:** Clear browser cache and re-login. Verify role in database:

```
sql SELECT * FROM users WHERE email = 'admin@hospital.com';

```
### Problem: Navigation not updating **Solution:**

 Ensure `rbac-permissions.js` loads before `app.js` in HTML:

```
html <script src="js/rbac-permissions.js"></script> <script src="js/app.js"></script>

```
### Problem: User can see unauthorized data **Solution:**

 Check server-side API filters. Frontend restrictions are UI-only. Server MUST filter data by role. ---

## Support **Files to check:**

 - `frontend/js/rbac-permissions.js`
- Permissions config
- `frontend/js/app.js`
- Integration code
- `backend/init_db.php` - Database structure
- `backend/add_rbac_users.php` - Sample users **Common Issues:**
 
1. Clear browser cache after changes 
2. Verify database migration ran successfully 
3. Check browser console for JavaScript errors 
4. Verify user role in database matches permissions config --
-

**Version:**
 2.0 **Date:**
 October 29, 2025 **Status:**
 Production Ready
 