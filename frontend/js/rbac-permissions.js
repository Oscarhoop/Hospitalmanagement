const RBAC = {
    roles: {
        admin: {
            label: 'Administrator',
            color: '#ef4444',
            icon: '',
            description: 'Full system access'
        },
        receptionist: {
            label: 'Receptionist',
            color: '#3b82f6',
            icon: '',
            description: 'Patient registration & appointments'
        },
        doctor: {
            label: 'Doctor',
            color: '#10b981',
            icon: '',
            description: 'Medical care & prescriptions'
        },
        nurse: {
            label: 'Nurse',
            color: '#8b5cf6',
            icon: '',
            description: 'Patient care & vitals'
        },
        pharmacist: {
            label: 'Pharmacist',
            color: '#f59e0b',
            icon: '',
            description: 'Medication dispensing'
        },
        lab_tech: {
            label: 'Lab Technician',
            color: '#06b6d4',
            icon: '',
            description: 'Laboratory testing'
        },
        billing: {
            label: 'Billing Officer',
            color: '#ec4899',
            icon: '',
            description: 'Financial management'
        }
    },
    
    permissions: {
        admin: {
            dashboard: { view: true, metrics: 'all' },
            patients: { view: 'all', add: true, edit: true, delete: true },
            appointments: { view: 'all', add: true, edit: true, delete: true, assign: true },
            doctors: { view: true, add: true, edit: true, delete: true },
            rooms: { view: true, add: true, edit: true, delete: true },
            medical_records: { view: 'all', add: true, edit: true, delete: true },
            prescriptions: { view: 'all', create: true, edit: true, dispense: true },
            lab_tests: { view: 'all', order: true, perform: true, verify: true },
            vital_signs: { view: 'all', record: true, edit: true },
            billing: { view: 'all', create: true, edit: true, delete: true, reports: true },
            reports: { view: true, export: true, financial: true, medical: true },
            users: { view: true, add: true, edit: true, delete: true },
            schedules: { view: true, add: true, edit: true, delete: true, approve_leave: true },
            audit: { view: true },
            navigation: ['dashboard', 'patients', 'appointments', 'doctors', 'rooms', 'medical-records', 'prescriptions', 'lab-tests', 'vital-signs', 'billing', 'reports', 'users', 'schedules', 'audit-trail']
        },
        
        receptionist: {
            dashboard: { view: false },
            patients: { view: 'all', add: true, edit: true, delete: false },
            appointments: { view: 'all', add: true, edit: true, delete: false, assign: true },
            doctors: { view: true, add: false, edit: false, delete: false },
            rooms: { view: true, add: false, edit: false, delete: false },
            medical_records: { view: false },
            prescriptions: { view: false },
            lab_tests: { view: false },
            vital_signs: { view: false },
            billing: { view: 'all', create: true, edit: false, delete: false, reports: false },
            reports: { view: false },
            users: { view: false },
            audit: { view: false },
            navigation: ['patients', 'appointments', 'doctors', 'rooms', 'billing', 'schedules']
        },
        
        doctor: {
            dashboard: { view: false },
            patients: { view: 'assigned', add: false, edit: true, delete: false },
            appointments: { view: 'own', add: false, edit: true, delete: false, assign: false },
            doctors: { view: true, add: false, edit: false, delete: false },
            medical_records: { view: 'assigned', add: true, edit: true, delete: false },
            prescriptions: { view: 'own', create: true, edit: true, dispense: false },
            lab_tests: { view: 'own', order: true, perform: false, verify: true },
            vital_signs: { view: 'assigned', record: false, edit: false },
            billing: { view: false },
            reports: { view: true, export: false, financial: false, medical: true },
            users: { view: false },
            audit: { view: false },
            navigation: ['patients', 'appointments', 'medical-records', 'doctors']
        },
        
        nurse: {
            dashboard: { view: false },
            patients: { view: 'all', add: false, edit: false, delete: false },
            appointments: { view: 'all', add: false, edit: true, delete: false, assign: false },
            doctors: { view: true, add: false, edit: false, delete: false },
            medical_records: { view: 'all', add: true, edit: true, delete: false },
            prescriptions: { view: 'all', create: false, edit: false, dispense: false },
            lab_tests: { view: 'all', order: false, perform: false, verify: false },
            vital_signs: { view: 'all', record: true, edit: true },
            billing: { view: false },
            reports: { view: false },
            users: { view: false },
            audit: { view: false },
            navigation: ['patients', 'appointments', 'doctors', 'medical_records', 'schedules']
        },
        
        pharmacist: {
            dashboard: { view: false },
            patients: { view: 'limited', add: false, edit: false, delete: false },
            appointments: { view: false },
            doctors: { view: true, add: false, edit: false, delete: false },
            medical_records: { view: false },
            prescriptions: { view: 'all', create: false, edit: false, dispense: true },
            lab_tests: { view: false },
            vital_signs: { view: false },
            billing: { view: false },
            reports: { view: false },
            users: { view: false },
            audit: { view: false },
            navigation: ['doctors']
        },
        
        lab_tech: {
            dashboard: { view: false },
            patients: { view: 'limited', add: false, edit: false, delete: false },
            appointments: { view: false },
            doctors: { view: true, add: false, edit: false, delete: false },
            medical_records: { view: false },
            prescriptions: { view: false },
            lab_tests: { view: 'all', order: false, perform: true, verify: false },
            vital_signs: { view: false },
            billing: { view: false },
            reports: { view: false },
            users: { view: false },
            audit: { view: false },
            navigation: ['doctors']
        },
        
        billing: {
            dashboard: { view: false },
            patients: { view: 'all', add: false, edit: false, delete: false },
            appointments: { view: false },
            doctors: { view: false },
            medical_records: { view: false },
            prescriptions: { view: false },
            lab_tests: { view: false },
            vital_signs: { view: false },
            billing: { view: 'all', create: true, edit: true, delete: false, reports: true },
            reports: { view: true, export: true, financial: true, medical: false },
            users: { view: false },
            audit: { view: false },
            navigation: ['billing', 'reports', 'patients', 'schedules']
        }
    },
    
    hasPermission(userRole, resource, action) {
        if (!this.permissions[userRole]) return false;
        if (!this.permissions[userRole][resource]) return false;
        
        const permission = this.permissions[userRole][resource][action];
        return permission === true || permission === 'all';
    },
    
    getAccessLevel(userRole, resource) {
        if (!this.permissions[userRole]) return null;
        return this.permissions[userRole][resource] || null;
    },
    
    getNavigation(userRole) {
        if (!this.permissions[userRole]) return [];
        return this.permissions[userRole].navigation || [];
    },
    
    getDashboardMetrics(userRole) {
        if (!this.permissions[userRole]) return 'none';
        return this.permissions[userRole].dashboard?.metrics || 'none';
    },
    
    canViewPatient(userRole, patientId, userAssignments = []) {
        const access = this.getAccessLevel(userRole, 'patients');
        if (!access || !access.view) return false;
        
        if (access.view === 'all') return true;
        if (access.view === 'assigned') {
            return userAssignments.includes(patientId);
        }
        if (access.view === 'limited') {
            return 'limited';
        }
        
        return false;
    },
    
    getRoleInfo(userRole) {
        return this.roles[userRole] || {
            label: 'Unknown',
            color: '#6b7280',
            icon: '',
            description: 'Unknown role'
        };
    }
};

window.RBAC = RBAC;

window.hasPermission = function(resource, action) {
    if (!currentUser || !currentUser.role) return false;
    return RBAC.hasPermission(currentUser.role, resource, action);
};

window.getAccessLevel = function(resource) {
    if (!currentUser || !currentUser.role) return null;
    return RBAC.getAccessLevel(currentUser.role, resource);
};

window.canViewPatient = function(patientId) {
    if (!currentUser || !currentUser.role) return false;
    const assignments = currentUser.patient_assignments || [];
    return RBAC.canViewPatient(currentUser.role, patientId, assignments);
};

console.log('✓ RBAC Permissions system loaded');
