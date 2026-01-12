/**
 * ============================================================
 * Sri Balathandayuthapani Temple System
 * Admin Panel JavaScript
 * 
 * Purpose: All JavaScript functions for admin pages
 * Created by: Avenesh A/L Kumaran (1221106783)
 * Last Modified: December 2025
 * ============================================================
 */

// ============================================================
// FORM VALIDATION & LOADING
// ============================================================

/**
 * Add loading animation when form is submitted
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Login form loading animation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            if (btn) {
                btn.classList.add('loading');
            }
        });
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        });
    }, 5000);
    
});

// ============================================================
// CONFIRMATION DIALOGS
// ============================================================

/**
 * Confirm delete action
 */
function confirmDelete(itemName) {
    return confirm('Are you sure you want to delete ' + itemName + '? This action cannot be undone.');
}

/**
 * Confirm logout
 */
function confirmLogout() {
    return confirm('Are you sure you want to logout?');
}

// ============================================================
// FORM VALIDATION
// ============================================================

/**
 * Validate email format
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate phone number (Malaysian format)
 */
function validatePhone(phone) {
    const re = /^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/;
    return re.test(phone);
}

/**
 * Validate required fields
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(function(input) {
        if (!input.value.trim()) {
            input.style.borderColor = '#c00';
            isValid = false;
        } else {
            input.style.borderColor = '#e0e0e0';
        }
    });
    
    return isValid;
}

// ============================================================
// SEARCH & FILTER FUNCTIONS
// ============================================================

/**
 * Search table rows
 */
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        let found = false;
        const td = tr[i].getElementsByTagName('td');
        
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
}

// ============================================================
// DATE & TIME FUNCTIONS
// ============================================================

/**
 * Format date to dd/mm/yyyy
 */
function formatDate(date) {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return day + '/' + month + '/' + year;
}

/**
 * Get current date in YYYY-MM-DD format
 */
function getCurrentDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

/**
 * Show success message
 */
function showSuccess(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success';
    alertDiv.innerHTML = '<span class="alert-icon">✓</span><span>' + message + '</span>';
    
    const container = document.querySelector('.dashboard-container, .login-form');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto hide after 5 seconds
        setTimeout(function() {
            alertDiv.style.opacity = '0';
            setTimeout(function() {
                alertDiv.remove();
            }, 500);
        }, 5000);
    }
}

/**
 * Show error message
 */
function showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-error';
    alertDiv.innerHTML = '<span class="alert-icon">⚠️</span><span>' + message + '</span>';
    
    const container = document.querySelector('.dashboard-container, .login-form');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto hide after 5 seconds
        setTimeout(function() {
            alertDiv.style.opacity = '0';
            setTimeout(function() {
                alertDiv.remove();
            }, 500);
        }, 5000);
    }
}

/**
 * Print page
 */
function printPage() {
    window.print();
}

/**
 * Go back to previous page
 */
function goBack() {
    window.history.back();
}

// ============================================================
// DASHBOARD FUNCTIONS
// ============================================================

/**
 * Update dashboard statistics (can be called via AJAX)
 */
function updateStats() {
    // This can be enhanced with AJAX calls to fetch real-time data
    console.log('Statistics updated');
}

/**
 * Toggle sidebar menu (for mobile)
 */
function toggleMenu() {
    const menu = document.querySelector('.nav-menu');
    if (menu) {
        menu.classList.toggle('active');
    }
}

// ============================================================
// MODAL FUNCTIONS
// ============================================================

/**
 * Open modal
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

/**
 * Close modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// ============================================================
// EXPORT FUNCTIONS
// ============================================================

/**
 * Export table to CSV
 */
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            row.push(cols[j].innerText);
        }
        
        csv.push(row.join(','));
    }
    
    // Download CSV
    const csvString = csv.join('\n');
    const link = document.createElement('a');
    link.href = 'data:text/csv;charset=utf-8,' + encodeURI(csvString);
    link.download = filename + '.csv';
    link.click();
}

// ============================================================
// SESSION TIMEOUT WARNING
// ============================================================

/**
 * Warn user before session expires
 */
let sessionTimeout;
function startSessionTimer(minutes) {
    const milliseconds = minutes * 60 * 1000;
    
    sessionTimeout = setTimeout(function() {
        alert('Your session will expire in 5 minutes due to inactivity. Please save your work.');
    }, milliseconds - 300000); // 5 minutes before expiry
}

// Reset timer on user activity
document.addEventListener('mousemove', function() {
    clearTimeout(sessionTimeout);
    startSessionTimer(30); // 30 minutes session
});

// ============================================================
// END OF JAVASCRIPT
// ============================================================