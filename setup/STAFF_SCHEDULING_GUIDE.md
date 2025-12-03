# Staff Scheduling System - Installation & Usage Guide

## Overview

The Staff Scheduling System allows administrators to manage staff shifts, schedules, and leave requests efficiently. It features conflict detection, shift templates, and calendar views.

---

## Installation Steps 

### Step 1: Run Database Migration

```bash
cd C:\xampp\htdocs\Hospital-management\backend
php setup_scheduling_tables.php
```
**This creates:**

- `shift_templates` - Pre-defined shift patterns (Morning, Afternoon, Night shifts)
- `staff_schedules` - Individual staff shift assignments
- `leave_requests` - Staff leave/vacation requests
- `shift_swaps` - Shift exchange requests (future feature)

**Default Shift Templates Created:**

- Morning Shift (8 AM - 4 PM)
- Afternoon Shift (2 PM - 10 PM)
- Night Shift (10 PM - 6 AM)
- Day Shift (9 AM - 5 PM)
- Extended Shift (7 AM - 7 PM) 

### Step 2: Refresh Browser

Clear browser cache (Ctrl+Shift+Delete) and reload the application.

---

## Features

### 1. **Expandable Staff Management Menu**

 The sidebar now has a grouped menu structure:

```
Staff Management Doctors Scheduling Users

```
- Click the arrow to expand/collapse
- Keeps sidebar clean and organized
- Easy navigation between related features

### 2. **Schedule Management**

 

#### Create Schedules

- Assign staff to specific dates and shifts
- Choose from predefined shift templates
- Auto-fills start/end times when template is selected
- Conflict detection prevents double-booking

#### Edit Schedules

- Update shift times
- Change assigned staff - Modify status (Scheduled → Completed/Cancelled) 

#### View Options 

- ** List View**
 - Table format with filters 
- ** Calendar View**
 - Monthly overview (coming soon) 
- ** Leave Requests**
 - Manage time-off requests 

### 

3. **Leave Request System**

 

#### Staff Can:

- Submit leave requests with dates and reason
- Choose leave type (Vacation, Sick, Personal, Emergency, etc.)
- View request status 

#### Managers Can:

- Approve or reject requests
- Add rejection reason if needed
- View all pending requests
- Filter by status 

### 

4. **Conflict Detection**


- Prevents scheduling staff for overlapping shifts
- Checks time conflicts automatically
- Displays helpful error messages ---

## Usage Examples 

### Example 1: Schedule a Nurse for Morning Shift 

1. Navigate to **Staff Management → Scheduling**
 
2. Click **"+ Add Schedule"**
 
3. Select staff member: "Jane Doe (Nurse)" 
4. Select shift template: "Morning Shift" 
5. Times auto-fill: 8:00 AM - 4:00 PM 
6. Choose date: e.g., 2025-11-05 
7. Click **"Save Schedule"**
 

### Example 2: Request Leave 

1. Go to **Scheduling → Leave Requests**
 tab 
2. Click **"+ Request Leave"**
 
3. Select staff member 
4. Choose leave type: "Vacation" 
5. Set dates: Start: 2025-12-20, End: 2025-12-27 
6. Enter reason: "Family holiday" 
7. Submit 

### Example 3: Approve Leave Request 

1. Go to **Leave Requests**
 tab 
2. Find pending request 
3. Click **"Approve"**
 or **"Reject"**
 
4. If rejecting, provide optional reason 
5. Status updates automatically ---

## Filters & Search 

### Schedule Filters: 

- **Staff Member**
 - View specific person's shifts 
- **Department**
 - Filter by Administration, Medical, Nursing, etc. 
- **Date Range**
 - View schedules between specific dates 

### Leave Request Filters: 

- **Status**
 - Pending, Approved, Rejected ---

## Database Schema 

### shift_templates

```
sql
- id
- name (e.g., "Morning Shift")
- start_time (e.g., "08:00:00")
- end_time (e.g., "16:00:00")
- color (hex code for visual distinction)
- description
- is_active
- created_at

```
### staff_schedules

```
sql
- id
- user_id (FK → users.id)
- shift_template_id (FK → shift_templates.id)
- schedule_date
- start_time
- end_time
- status (scheduled/completed/cancelled)
- notes
- created_by
- created_at
- updated_at

```
### leave_requests

```
sql
- id
- user_id (FK → users.id)
- leave_type (Vacation, Sick Leave, etc.)
- start_date
- end_date
- reason
- status (pending/approved/rejected)
- approved_by (FK → users.id)
- approved_at
- rejection_reason
- created_at - updated_at

```

---

## Permissions (Future Enhancement) Recommended RBAC permissions: 

| Role 

| View Schedules 

| Create/Edit 

| Approve Leave 

| Request Own Leave 

| 

|---

---

|---

----

----

----

-

|---

----

----

--

|---

----

----

----

|---

----

----

----

----

| 

| **Admin**
 

| All 

| All 

| Yes 

| Yes 

| 

| **Department Head**
 

| Department 

| Department 

| Department 

| Yes 

| 

| **Receptionist**
 

| All 

| No 

| No 

| Yes 

| 

| **Doctor/Nurse**
 

| Own Only 

| No 

| No 

| Yes 

| ---

## UI Components 

### Status Badges: 

- **Scheduled**
 - Blue 
- **Completed**
 - Green 
- **Cancelled**
 - Gray 
- **Pending**
 (Leave) - Yellow 
- **Approved**
 (Leave) - Green 
- **Rejected**
 (Leave) - Red 

### Shift Color Indicators: Each shift template has a color dot for quick visual identification in the schedule table. ---

## Troubleshooting 

### Problem: "Failed to load schedules" **Solution:**


- Ensure migration ran successfully
- Check browser console for API errors
- Verify `backend/api/schedules.php` exists 

### Problem: Expandable menu not working **Solution:**


- Clear browser cache
- Check `frontend/css/styles.css` has expandable menu styles
- Verify `scheduling.js` is loaded 

### Problem: Conflict detection not working **Solution:**


- Check time format in database (should be HH:MM:SS)
- Verify dates are in YYYY-MM-DD format ---

## Future Enhancements 

### Phase 2 (Planned): 

1. **Full Calendar View**
 - Interactive monthly calendar with drag-drop 
2. **Shift Swapping**

- Allow staff to request shift exchanges 
3. **Recurring Schedules**

- Auto-create weekly patterns 
4. **Overtime Tracking**

- Calculate hours worked 
5. **Mobile Notifications**

- Alerts for upcoming shifts 
6. **CSV Export**

- Download schedule reports 
7. **Availability Management**

- Staff set their available hours ---

## API Endpoints 

### Schedules

```
GET /backend/api/schedules.php
- List schedules GET /backend/api/schedules.php?id=123
- Get schedule POST /backend/api/schedules.php
- Create schedule PUT /backend/api/schedules.php?id=123
- Update schedule DELETE /backend/api/schedules.php?id=123
- Cancel schedule GET /backend/api/schedules.php?action=templates
- Get shift templates

```
### Leave Requests

```
GET /backend/api/schedules.php?action=leave_requests
- List leave requests POST /backend/api/schedules.php (action=leave_request)
- Submit leave PUT /backend/api/schedules.php?id=123 (action=approve_leave) - Approve/reject

```

---

## Testing Checklist

- [ ] Migration ran without errors
- [ ] Can expand/collapse Staff Management menu
- [ ] Can create new schedule
- [ ] Shift template auto-fills times - [ ] Conflict detection prevents double-booking
- [ ] Can edit existing schedule
- [ ] Can cancel schedule
- [ ] Can submit leave request
- [ ] Can approve/reject leave
- [ ] Filters work correctly
- [ ] Status badges display correctly ---

## Notes 

- **Time Format:**
 All times are stored in 24-hour format (HH:MM:SS) 
- **Date Format:**
 All dates are YYYY-MM-DD 
- **Session Required:**
 User must be logged in to access scheduling 
- **Conflict Logic:**
 Checks for ANY overlap in time ranges on the same date --
-

**Version:**
 1.0 **Date:**
 November 2, 2025 **Status:**
 Production Ready For questions or issues, refer to the main README.md or check the browser console for detailed error messages.
 