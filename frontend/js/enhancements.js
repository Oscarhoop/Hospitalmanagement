let globalSearchTimeout = null;

function initGlobalSearch() {
    const headerActions = document.querySelector('.header-actions');
    if (headerActions && !document.getElementById('globalSearchBtn')) {
        const searchBtn = document.createElement('button');
        searchBtn.id = 'globalSearchBtn';
        searchBtn.className = 'btn btn-secondary btn-sm';
        searchBtn.innerHTML = '<i class="fas fa-search"></i> Search';
        searchBtn.onclick = showGlobalSearchModal;
        headerActions.insertBefore(searchBtn, headerActions.firstChild);
    }
}

function showGlobalSearchModal() {
    if (!document.getElementById('globalSearchModal')) {
        const modal = document.createElement('div');
        modal.id = 'globalSearchModal';
        modal.className = 'modal hidden';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title"><i class="fas fa-search"></i> Global Search</h2>
                    <button class="close-btn" onclick="hideGlobalSearch()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <input type="text" id="globalSearchInput" class="form-control" 
                               placeholder="Search patients, appointments, doctors..." 
                               autofocus>
                    </div>
                    <div id="globalSearchResults" style="max-height: 500px; overflow-y: auto;">
                        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            <div style="font-size: 3rem; margin-bottom: 1rem;"><i class="fas fa-search"></i></div>
                            <div>Type to search across all records</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Add event listeners
        document.getElementById('globalSearchInput').addEventListener('input', 
            debounce(performGlobalSearch, 300));
        document.getElementById('globalSearchInput').addEventListener('keydown', handleSearchKeydown);
    }
    
    const modal = document.getElementById('globalSearchModal');
    modal.classList.remove('hidden');
    setTimeout(() => modal.classList.add('show'), 10);
    document.getElementById('globalSearchInput').focus();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

async function performGlobalSearch() {
    const query = document.getElementById('globalSearchInput').value.trim();
    
    if (query.length < 2) {
        document.getElementById('globalSearchResults').innerHTML = `
            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                <div>Please enter at least 2 characters</div>
            </div>
        `;
        return;
    }
    
    // Show loading state
    document.getElementById('globalSearchResults').innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <div class="loading" style="margin: 0 auto;"></div>
            <div style="margin-top: 1rem;">Searching...</div>
        </div>
    `;
    
    try {
        // Check if API_BASE is available
        if (typeof API_BASE === 'undefined') {
            throw new Error('API_BASE is not defined');
        }
        
        console.log('Performing global search with query:', query);
        console.log('Using API_BASE:', API_BASE);
        
        // Execute all searches in parallel
        const [patientsRes, appointmentsRes, doctorsRes] = await Promise.all([
            fetch(`${API_BASE}patients.php?search=${encodeURIComponent(query)}`),
            fetch(`${API_BASE}appointments.php?search=${encodeURIComponent(query)}`),
            fetch(`${API_BASE}doctors.php?search=${encodeURIComponent(query)}`)
        ]);
        
        console.log('Search responses received:', {
            patients: patientsRes.status,
            appointments: appointmentsRes.status, 
            doctors: doctorsRes.status
        });
        
        const patients = await patientsRes.json();
        const appointments = await appointmentsRes.json();
        const doctors = await doctorsRes.json();
        
        console.log('Search results:', { patients, appointments, doctors });
        
        displayGlobalSearchResults(patients, appointments, doctors, query);
    } catch (err) {
        console.error('Global search error:', err);
        document.getElementById('globalSearchResults').innerHTML = `
            <div style="text-align: center; padding: 2rem; color: var(--danger);">
                Error searching: ${err.message}
            </div>
        `;
    }
}

function hideGlobalSearch() {
    const modal = document.getElementById('globalSearchModal');
    modal.classList.remove('show');
    setTimeout(() => modal.classList.add('hidden'), 300);
    document.getElementById('globalSearchInput').value = '';
    document.getElementById('globalSearchResults').innerHTML = `
        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
            <div style="font-size: 3rem; margin-bottom: 1rem;"><i class="fas fa-search"></i></div>
            <div>Type to search across all records</div>
        </div>
    `;
    currentSearchSelection = -1; // Reset selection
}

let currentSearchSelection = -1;

function handleSearchKeydown(e) {
    const results = document.querySelectorAll('#globalSearchResults > div > div');
    if (!results.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        currentSearchSelection = Math.min(currentSearchSelection + 1, results.length - 1);
        updateSearchSelection(results);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        currentSearchSelection = Math.max(currentSearchSelection - 1, 0);
        updateSearchSelection(results);
    } else if (e.key === 'Enter' && currentSearchSelection >= 0) {
        e.preventDefault();
        results[currentSearchSelection].click();
    }
}

function updateSearchSelection(results) {
    results.forEach((result, index) => {
        if (index === currentSearchSelection) {
            result.style.backgroundColor = 'var(--primary-light)';
            result.scrollIntoView({ block: 'nearest' });
        } else {
            result.style.backgroundColor = '';
        }
    });
}

function displayGlobalSearchResults(patients, appointments, doctors, query) {
    const resultsDiv = document.getElementById('globalSearchResults');
    let html = '';
    
    const highlight = (text) => {
        if (!text) return '';
        const regex = new RegExp(`(${query})`, 'gi');
        return String(text).replace(regex, '<mark style="background: #fef3c7; padding: 0 2px; border-radius: 2px;">$1</mark>');
    };
    
    if (patients && patients.length > 0) {
        html += '<div style="margin-bottom: 1.5rem;"><h3 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;"><i class="fas fa-users"></i> Patients (' + patients.length + ')</h3>';
        patients.slice(0, 10).forEach(p => {
            html += `
                <div style="padding: 0.75rem; background: var(--bg-secondary); margin-bottom: 0.5rem; border-radius: var(--radius); cursor: pointer;" 
                     onclick="navigateToPatient(${p.id})">
                    <div style="font-weight: 500;">${highlight(p.first_name)} ${highlight(p.last_name)}</div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                        ${p.phone ? '📞 ' + highlight(p.phone) : ''} ${p.email ? '📧 ' + highlight(p.email) : ''}
                    </div>
                </div>
            `;
        });
        if (patients.length > 10) {
            html += `<div style="text-align: center; color: var(--text-muted); font-size: 0.875rem;">... and ${patients.length - 10} more</div>`;
        }
        html += '</div>';
    }
    
    if (doctors && doctors.length > 0) {
        html += '<div style="margin-bottom: 1.5rem;"><h3 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;"><i class="fas fa-user-md"></i> Doctors (' + doctors.length + ')</h3>';
        doctors.slice(0, 10).forEach(d => {
            html += `
                <div style="padding: 0.75rem; background: var(--bg-secondary); margin-bottom: 0.5rem; border-radius: var(--radius); cursor: pointer;"
                     onclick="navigateToDoctor(${d.id})">
                    <div style="font-weight: 500;">${highlight(d.first_name)} ${highlight(d.last_name)}</div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                        ${d.specialty ? '🩺 ' + highlight(d.specialty) : ''} ${d.phone ? '📞 ' + highlight(d.phone) : ''}
                    </div>
                </div>
            `;
        });
        if (doctors.length > 10) {
            html += `<div style="text-align: center; color: var(--text-muted); font-size: 0.875rem;">... and ${doctors.length - 10} more</div>`;
        }
        html += '</div>';
    }
    
    if (appointments && appointments.length > 0) {
        html += '<div><h3 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;"><i class="fas fa-calendar"></i> Appointments (' + appointments.length + ')</h3>';
        appointments.slice(0, 10).forEach(a => {
            html += `
                <div style="padding: 0.75rem; background: var(--bg-secondary); margin-bottom: 0.5rem; border-radius: var(--radius); cursor: pointer;"
                     onclick="navigateToAppointment(${a.id})">
                    <div style="font-weight: 500;">${highlight(a.patient_first_name || '')} ${highlight(a.patient_last_name || '')} → ${highlight(a.doctor_first_name || '')} ${highlight(a.doctor_last_name || '')}</div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                        <i class="fas fa-calendar"></i> ${formatDateTime(a.start_time)} • ${a.status ? '<span class="status-badge status-' + a.status + '">' + a.status + '</span>' : ''}
                    </div>
                </div>
            `;
        });
        if (appointments.length > 10) {
            html += `<div style="text-align: center; color: var(--text-muted); font-size: 0.875rem;">... and ${appointments.length - 10} more</div>`;
        }
        html += '</div>';
    }
    
    if (!html) {
        html = `
            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                <div style="font-size: 3rem; margin-bottom: 1rem;"><i class="fas fa-search"></i></div>
                <div>No results found for "${query}"</div>
            </div>
        `;
    }
    
    resultsDiv.innerHTML = html;
    
    // Add hover effect to result items
    const results = document.querySelectorAll('#globalSearchResults > div > div');
    results.forEach(result => {
        result.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'var(--primary-light)';
        });
        result.addEventListener('mouseleave', function() {
            if (!this.classList.contains('selected')) {
                this.style.backgroundColor = '';
            }
        });
    });
}

function navigateToPatient(id) {
    hideGlobalSearch();
    showSection('patients');
    setTimeout(() => editPatient(id), 300);
}

function navigateToDoctor(id) {
    hideGlobalSearch();
    showSection('doctors');
}

function navigateToAppointment(id) {
    hideGlobalSearch();
    showSection('appointments');
}

function initSidebarCollapsible() {
    document.body.classList.add('sidebar-collapsible');
    const compactPref = localStorage.getItem('sidebarCompact');
    const isCompact = compactPref === 'true';
    document.body.classList.toggle('sidebar-compact', isCompact);
    
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    if (toggleBtn) {
        toggleBtn.onclick = () => {
            const nowCompact = !document.body.classList.contains('sidebar-compact');
            document.body.classList.toggle('sidebar-compact', nowCompact);
            localStorage.setItem('sidebarCompact', String(nowCompact));
        };
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebarCollapsible);
} else {
    initSidebarCollapsible();
}

function exportToCSV(data, filename) {
    if (!data || data.length === 0) {
        showToast('Export Failed', 'No data to export', 'error');
        return;
    }
    
    const headers = Object.keys(data[0]);
    let csv = headers.join(',') + '\n';
    
    data.forEach(row => {
        const values = headers.map(header => {
            const value = row[header];
            if (value === null || value === undefined) return '';
            const stringValue = String(value).replace(/"/g, '""');
            return stringValue.includes(',') ? `"${stringValue}"` : stringValue;
        });
        csv += values.join(',') + '\n';
    });
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${filename}_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    
    showToast('Export Successful', `${filename} exported successfully`, 'success');
}

function exportToPDF(title, data) {
    showToast('PDF Export', 'PDF export requires jsPDF library. Opening print dialog instead.', 'success');
    window.print();
}

async function exportCurrentTable() {
    if (!currentUser || currentUser.role !== 'admin') {
        showToast('Not Allowed', 'Only administrators can export data', 'error');
        return;
    }

    const section = currentSection;
    let data = [];
    let filename = section;
    
    try {
        switch(section) {
            case 'patients':
                const patientsRes = await fetch(API_BASE + 'patients.php');
                data = await patientsRes.json();
                filename = 'patients';
                break;
            case 'appointments':
                const appointmentsRes = await fetch(API_BASE + 'appointments.php');
                data = await appointmentsRes.json();
                filename = 'appointments';
                break;
            case 'doctors':
                const doctorsRes = await fetch(API_BASE + 'doctors.php');
                data = await doctorsRes.json();
                filename = 'doctors';
                break;
            case 'billing':
                const billingRes = await fetch(API_BASE + 'billing.php');
                data = await billingRes.json();
                filename = 'billing';
                break;
            case 'users':
                const usersRes = await fetch(API_BASE + 'users.php');
                data = await usersRes.json();
                filename = 'users';
                break;
            default:
                showToast('Export Error', 'Cannot export this section', 'error');
                return;
        }
        
        if (data && data.length > 0) {
            exportToCSV(data, filename);
        } else {
            showToast('Export Failed', 'No data to export', 'error');
        }
    } catch (err) {
        showToast('Export Error', err.message, 'error');
    }
}

function addExportButtons() {}

let currentCalendarDate = new Date();
let calendarView = 'month'; // 'day', 'week', 'month'

function initCalendarView() {
    const appointmentsSection = document.getElementById('appointments');
    if (!appointmentsSection) return;
    
    if (document.getElementById('calendarViewContainer')) return;
    
    const pageHeader = appointmentsSection.querySelector('.page-header');
    if (!pageHeader) return;
    
    const toggleDiv = document.createElement('div');
    toggleDiv.id = 'calendarToggleButtons';
    toggleDiv.style.cssText = 'display: flex; gap: 0.5rem; margin-top: 1rem;';
    toggleDiv.innerHTML = `
        <button class="btn btn-secondary btn-sm" onclick="toggleAppointmentView('table')"><i class="fas fa-table"></i> Table View</button>
        <button class="btn btn-secondary btn-sm" onclick="toggleAppointmentView('calendar')"><i class="fas fa-calendar"></i> Calendar View</button>
    `;
    pageHeader.appendChild(toggleDiv);
    
    const calendarContainer = document.createElement('div');
    calendarContainer.id = 'calendarViewContainer';
    calendarContainer.style.display = 'none';
    calendarContainer.innerHTML = `
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <button class="btn btn-secondary btn-sm" onclick="navigateCalendar(-1)">← Previous</button>
                <h2 class="card-title" id="calendarTitle">Calendar</h2>
                <button class="btn btn-secondary btn-sm" onclick="navigateCalendar(1)">Next →</button>
            </div>
            <div class="card-body">
                <div id="calendarView"></div>
            </div>
        </div>
    `;
    
    const appointmentCard = appointmentsSection.querySelector('.card:last-child');
    if (appointmentCard) {
        appointmentCard.parentNode.insertBefore(calendarContainer, appointmentCard);
    }
}

function toggleAppointmentView(view) {
    const allCards = document.querySelectorAll('#appointments .card');
    const tableCard = Array.from(allCards).find(card => !card.id || card.id !== 'calendarViewContainer');
    const calendarContainer = document.getElementById('calendarViewContainer');
    
    if (!calendarContainer) {
        initCalendarView();
        setTimeout(() => toggleAppointmentView(view), 100);
        return;
    }
    
    if (view === 'calendar') {
        if (tableCard) tableCard.style.display = 'none';
        calendarContainer.style.display = 'block';
        renderCalendar();
    } else {
        if (tableCard) tableCard.style.display = 'block';
        calendarContainer.style.display = 'none';
    }
}

function navigateCalendar(direction) {
    currentCalendarDate.setMonth(currentCalendarDate.getMonth() + direction);
    renderCalendar();
}

async function renderCalendar() {
    const year = currentCalendarDate.getFullYear();
    const month = currentCalendarDate.getMonth();
    
    document.getElementById('calendarTitle').textContent = 
        currentCalendarDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    
    const startDate = new Date(year, month, 1).toISOString().split('T')[0];
    const endDate = new Date(year, month + 1, 0).toISOString().split('T')[0];
    
    try {
        const response = await fetch(`${API_BASE}appointments.php?start_date=${startDate}&end_date=${endDate}`);
        const appointments = await response.json();
        
        renderMonthCalendar(year, month, appointments);
    } catch (err) {
        document.getElementById('calendarView').innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--danger);">Error loading calendar</div>';
    }
}

function renderMonthCalendar(year, month, appointments) {
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    let html = '<div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem;">';
    
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    days.forEach(day => {
        html += `<div style="font-weight: 600; text-align: center; padding: 0.5rem; background: var(--bg-secondary); border-radius: var(--radius);">${day}</div>`;
    });
    
    for (let i = 0; i < firstDay; i++) {
        html += '<div style="background: var(--bg-tertiary); border-radius: var(--radius); min-height: 100px;"></div>';
    }
    
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayAppointments = appointments.filter(a => a.start_time && a.start_time.startsWith(dateStr));
        
        const isToday = dateStr === new Date().toISOString().split('T')[0];
        
        html += `
            <div style="background: ${isToday ? 'var(--primary-light)' : 'var(--bg-secondary)'}; 
                        border: ${isToday ? '2px solid var(--primary)' : '1px solid var(--border)'}; 
                        border-radius: var(--radius); 
                        padding: 0.5rem; 
                        min-height: 100px;
                        cursor: pointer;"
                 onclick="showDayAppointments('${dateStr}')">
                <div style="font-weight: 600; margin-bottom: 0.25rem;">${day}</div>
                ${dayAppointments.slice(0, 3).map(a => 
                    `<div style="font-size: 0.75rem; background: var(--primary); color: white; padding: 0.25rem; border-radius: 3px; margin-bottom: 0.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        ${a.patient_first_name || 'Unknown'} ${new Date(a.start_time).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}
                    </div>`
                ).join('')}
                ${dayAppointments.length > 3 ? `<div style="font-size: 0.7rem; color: var(--text-muted);">+${dayAppointments.length - 3} more</div>` : ''}
            </div>
        `;
    }
    
    html += '</div>';
    document.getElementById('calendarView').innerHTML = html;
}

function showDayAppointments(dateStr) {
    toggleAppointmentView('table');
    document.getElementById('appointmentDateFrom').value = dateStr;
    document.getElementById('appointmentDateTo').value = dateStr;
    filterAppointments();
}

function initEnhancements() {
    initGlobalSearch();
    addExportButtons();
    initCalendarView();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initEnhancements, 1000);
    });
} else {
    setTimeout(initEnhancements, 1000);
}
