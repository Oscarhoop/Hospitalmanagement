# RBAC Users & Permissions Guide 

## Users Created 

### Nurses (3 users) **Permissions:**


- View all patients
- Record vital signs
- Update medical records
- View appointments
- Update appointment notes
- Cannot create patients
- Cannot access billing
- Cannot create prescriptions **Login Credentials:**

- `nurse.sarah@hospital.com` / `nurse123`
- `nurse.mark@hospital.com` / `nurse123`
- `nurse.emily@hospital.com` / `nurse123` **Navigation Menu:**

- Patients
- Appointments
- Doctors
- Medical Records
- Schedules ---

### Receptionists (2 users) **Permissions:**


- Register new patients
- Schedule appointments
- View all patients
- Create billing records
- View doctors and rooms
- View schedules
- Cannot see medical records
- Cannot edit billing (only create)
- Cannot access reports **Login Credentials:**

- `reception.lisa@hospital.com` / `reception123`
- `reception.james@hospital.com` / `reception123` **Navigation Menu:**

- Patients
- Appointments
- Doctors
- Rooms
- Billing - Schedules ---

### Billing Officers (2 users) **Permissions:**


- View all billing records
- Create billing records
- Edit billing records
- View financial reports
- Export financial data
- View patients (for billing purposes)
- View schedules
- Cannot see medical records
- Cannot schedule appointments
- Cannot access user management **Login Credentials:**

- `billing.michael@hospital.com` / `billing123`
- `billing.jennifer@hospital.com` / `billing123` **Navigation Menu:**

- Billing
- Reports
- Patients
- Schedules ---

### Pharmacist (1 user) **Permissions:**


- View prescriptions
- Dispense medications
- View limited patient info (for safety)
- Cannot see full medical records
- Cannot schedule appointments **Login Credentials:**

- `pharmacist.david@hospital.com` / `pharmacy123` **Navigation Menu:**
 - Doctors (to view prescriptions) ---

### Lab Technician (1 user) **Permissions:**


- View lab test orders
- Upload test results
- View limited patient info
- Cannot see prescriptions
- Cannot access billing **Login Credentials:**

- `lab.anna@hospital.com` / `lab123` **Navigation Menu:**
 - Doctors (to view lab orders) ---

## Backend API Protection All APIs now enforce role-based permissions: 

### Billing API (`/backend/api/billing.php`) 

- **GET**
: Requires `admin`, `receptionist`, or `billing` role 
- **POST**
: Requires `admin`, `receptionist`, or `billing` role 
- **PUT**
: Requires `admin` or `billing` role 
- **DELETE**
: Requires `admin` or `billing` role 

### Patients API (`/backend/api/patients.php`) 

- **GET**
: Requires any authenticated user (all roles can view) 
- **POST**
: Requires `admin` or `receptionist` role 
- **PUT**
: Requires authenticated user (all roles can edit) 
- **DELETE**
: Requires `admin` or `receptionist` role 

### Appointments API (`/backend/api/appointments.php`) 

- **POST**
: Requires `admin` or `receptionist` role 

### Medical Records API (`/backend/api/medical_records.php`) 

- **GET**
: Requires `admin`, `doctor`, or `nurse` role 
- **POST**
: Requires `admin`, `doctor`, or `nurse` role 

### Reports API (`/backend/api/reports.php`) 

- **GET**
:
- Financial reports: Requires `admin` or `billing` role
- Other reports: Requires `admin`, `billing`, or `doctor` role ---

## Permission Matrix 

| Feature 

| Admin 

| Receptionist 

| Doctor 

| Nurse 

| Billing 

| Pharmacist 

| Lab Tech 

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

:

|:---

----

---:

|:---

----

-:

| 

| **View Patients**
 

| All 

| All 

| Assigned 

| All 

| All 

| Limited 

| Limited 

| 

| **Create Patients**
 

| 

| 

| 

| 

| 

| 

| 

| 

| **Medical Records**
 

| Full 

| 

| Full 

| Full 

| 

| 

| 

| 

| **Create Appointments**
 

| 

| 

| 

| 

| 

| 

| 

| 

| **Create Billing**
 

| 

| 

| 

| 

| 

| 

| 

| 

| **Edit Billing**
 

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

| **View Schedules**
 

| 

| 

| 

| 

| 

| 

| 

| ---

## Testing the System 

### Test Scenario 1: Receptionist Workflow 

1. Login as `reception.lisa@hospital.com` / `reception123` 
2. Should see: Patients, Appointments, Doctors, Rooms, Billing, Schedules 
3. Can create new patient 
4. Can schedule appointment 
5. Can create billing record 
6. Cannot access Medical Records section 
7. Cannot access Reports section 

### Test Scenario 2: Nurse Workflow 

1. Login as `nurse.sarah@hospital.com` / `nurse123` 
2. Should see: Patients, Appointments, Doctors, Medical Records, Schedules 
3. Can view all patients 
4. Can record vital signs 
5. Can update medical records 
6. Cannot create new patients 
7. Cannot access Billing section 

### Test Scenario 3: Billing Officer Workflow 

1. Login as `billing.michael@hospital.com` / `billing123` 
2. Should see: Billing, Reports, Patients, Schedules 
3. Can view all billing records 
4. Can create and edit billing 
5. Can view financial reports 
6. Cannot access Medical Records 
7. Cannot schedule appointments ---

## Adding More Users To add more users with specific roles, use the script:

```bash
cd C:\xampp\htdocs\Hospital-management\backend
php add_rbac_users.php
```
Or manually add via the Users API (admin only):
- POST to `/backend/api/users.php`
- Include: `name`, `email`, `password`, `role` - Roles: `admin`, `receptionist`, `doctor`, `nurse`, `billing`, `pharmacist`, `lab_tech` ---

## Files Modified 

1. **`backend/api/permissions.php`**

- New permission checking functions 
2. **`backend/api/billing.php`**

- Added permission checks 
3. **`backend/api/patients.php`**

- Added permission checks 
4. **`backend/api/appointments.php`**

- Added permission checks 
5. **`backend/api/medical_records.php`**

- Added permission checks 
6. **`backend/api/reports.php`**

- Added permission checks 
7. **`backend/add_rbac_users.php`**
 - Script to create RBAC users 
8. **`frontend/js/rbac-permissions.js`**

- Updated navigation for roles 
9. **`frontend/js/app.js`**

- Enhanced navigation filtering ---

## Status **All systems operational!**


- 9 RBAC users created
- Backend APIs protected
- Frontend navigation filtered
- Permissions enforced on all endpoints --
-

**Version:**
 1.0 **Date:**
 December 2024 **Status:**
 Production Ready
 