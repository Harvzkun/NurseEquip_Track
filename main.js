// Global utility functions

// Format date to readable format
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Calculate time remaining until due date
function getTimeRemaining(dueDate) {
    const now = new Date().getTime();
    const due = new Date(dueDate).getTime();
    const diff = due - now;
    
    if (diff <= 0) {
        return {
            expired: true,
            text: 'Overdue',
            class: 'overdue'
        };
    }
    
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    
    let statusClass = 'borrowed';
    let statusText = '';
    
    if (days > 0) {
        statusText = `${days} day${days > 1 ? 's' : ''} left`;
        if (days <= 2) {
            statusClass = 'due-soon';
        }
    } else if (hours > 0) {
        statusText = `${hours} hour${hours > 1 ? 's' : ''} left`;
        statusClass = 'due-soon';
    } else {
        statusText = `${minutes} minute${minutes > 1 ? 's' : ''} left`;
        statusClass = 'due-soon';
    }
    
    return {
        expired: false,
        text: statusText,
        class: statusClass,
        days: days,
        hours: hours,
        minutes: minutes
    };
}

// Show loading spinner
function showLoading(container) {
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    spinner.id = 'loading-spinner';
    container.innerHTML = '';
    container.appendChild(spinner);
}

// Hide loading spinner
function hideLoading() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

// Show notification toast
function showToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        `;
        document.body.appendChild(toastContainer);
    }
    
    // Create toast
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.cssText = `
        margin-bottom: 10px;
        min-width: 250px;
        animation: slideIn 0.3s ease;
    `;
    toast.innerHTML = message;
    
    // Add to container
    toastContainer.appendChild(toast);
    
    // Remove after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 5000);
}

// Confirm action
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Validate email format
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validate phone number (simple validation)
function isValidPhone(phone) {
    const re = /^[\d\s\-+()]{10,}$/;
    return re.test(phone);
}

// Debounce function for search inputs
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

// Search table rows
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        let found = false;
        const cells = row.getElementsByTagName('td');
        
        for (let j = 0; j < cells.length; j++) {
            const cell = cells[j];
            if (cell) {
                const textValue = cell.textContent || cell.innerText;
                if (textValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        row.style.display = found ? '' : 'none';
    }
}

// Export table to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/,/g, ' '); // Remove commas to avoid CSV issues
            rowData.push('"' + data + '"');
        }
        
        csv.push(rowData.join(','));
    }
    
    // Download CSV
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Print table
function printTable(tableId, title) {
    const printWindow = window.open('', '_blank');
    const table = document.getElementById(tableId).cloneNode(true);
    const styles = document.querySelectorAll('style, link[rel="stylesheet"]');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>${title}</title>
                ${Array.from(styles).map(style => style.outerHTML).join('')}
                <style>
                    body { padding: 20px; }
                    .btn, .action-buttons { display: none; }
                    @media print {
                        .btn, .action-buttons { display: none; }
                    }
                </style>
            </head>
            <body>
                <h2>${title}</h2>
                ${table.outerHTML}
                <script>
                    window.onload = function() { window.print(); }
                </scr` + `ipt>
            </body>
        </html>
    `);
    
    printWindow.document.close();
}

// Auto-refresh data every 5 minutes (for dashboard)
function setupAutoRefresh(interval = 300000) {
    setTimeout(() => {
        location.reload();
    }, interval);
}

// Check for overdue items and show warning
function checkForOverdueItems() {
    const dueDateElements = document.querySelectorAll('[data-due-date]');
    
    dueDateElements.forEach(element => {
        const dueDate = element.dataset.dueDate;
        const timeLeft = getTimeRemaining(dueDate);
        
        if (timeLeft.expired) {
            element.classList.add('text-danger');
            element.innerHTML += ' (OVERDUE)';
        } else if (timeLeft.days <= 2) {
            element.classList.add('text-warning');
        }
    });
}

// Initialize tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', (e) => {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = element.dataset.tooltip;
            tooltip.style.cssText = `
                position: absolute;
                background: #333;
                color: white;
                padding: 5px 10px;
                border-radius: 5px;
                font-size: 12px;
                z-index: 1000;
            `;
            
            const rect = element.getBoundingClientRect();
            tooltip.style.top = rect.top - 30 + 'px';
            tooltip.style.left = rect.left + 'px';
            
            document.body.appendChild(tooltip);
            
            element.addEventListener('mouseleave', () => {
                tooltip.remove();
            }, { once: true });
        });
    });
}

// Run when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check for overdue items
    checkForOverdueItems();
    
    // Initialize tooltips
    initTooltips();
    
    // Add search functionality if search input exists
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', debounce(function() {
            searchTable('tableSearch', 'dataTable');
        }, 300));
    }
    
    // Add animation to alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'fadeOut 0.5s ease';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
    }
    
    .text-danger {
        color: #e74c3c !important;
        font-weight: bold;
    }
    
    .text-warning {
        color: #f39c12 !important;
        font-weight: bold;
    }
    
    .tooltip {
        position: absolute;
        background: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 12px;
        pointer-events: none;
        animation: fadeIn 0.2s ease;
    }
`;
document.head.appendChild(style);