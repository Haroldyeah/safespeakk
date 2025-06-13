// Main JavaScript file for Capstone Report Management System

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize file upload functionality
    initializeFileUpload();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize status updates
    initializeStatusUpdates();
});

// File Upload Functionality
function initializeFileUpload() {
    const fileUploadArea = document.querySelector('.file-upload-area');
    const fileInput = document.querySelector('#report_file');
    
    if (fileUploadArea && fileInput) {
        // Click to upload
        fileUploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        // Drag and drop functionality
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });
        
        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
        });
        
        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileDisplay(files[0]);
            }
        });
        
        // File input change
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                updateFileDisplay(this.files[0]);
            }
        });
    }
}

function updateFileDisplay(file) {
    const fileUploadArea = document.querySelector('.file-upload-area');
    const allowedTypes = ['pdf', 'doc', 'docx'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    
    if (!allowedTypes.includes(fileExtension)) {
        showAlert('Invalid file type. Please upload PDF, DOC, or DOCX files only.', 'danger');
        return;
    }
    
    if (file.size > 50 * 1024 * 1024) { // 50MB limit
        showAlert('File size exceeds 50MB limit.', 'danger');
        return;
    }
    
    fileUploadArea.innerHTML = `
        <div class="file-selected">
            <i class="fas fa-file-alt file-upload-icon text-success"></i>
            <h6 class="mt-2 mb-1">${file.name}</h6>
            <p class="text-muted mb-0">${formatFileSize(file.size)}</p>
            <small class="text-success">File ready for upload</small>
        </div>
    `;
}

// Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Focus on first invalid field
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}

// Search Functionality
function initializeSearch() {
    const searchInput = document.querySelector('#searchInput');
    const searchForm = document.querySelector('#searchForm');
    
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value);
            }, 300);
        });
    }
    
    // Advanced search toggle
    const advancedSearchToggle = document.querySelector('#advancedSearchToggle');
    const advancedSearchForm = document.querySelector('#advancedSearchForm');
    
    if (advancedSearchToggle && advancedSearchForm) {
        advancedSearchToggle.addEventListener('click', function() {
            advancedSearchForm.classList.toggle('d-none');
            this.innerHTML = advancedSearchForm.classList.contains('d-none') 
                ? '<i class="fas fa-search-plus me-1"></i>Advanced Search'
                : '<i class="fas fa-search-minus me-1"></i>Hide Advanced';
        });
    }
}

function performSearch(query) {
    const tableRows = document.querySelectorAll('.searchable-row');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query.toLowerCase()) || query === '') {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update results count
    const visibleRows = document.querySelectorAll('.searchable-row[style=""], .searchable-row:not([style])');
    const resultsCount = document.querySelector('#resultsCount');
    if (resultsCount) {
        resultsCount.textContent = `${visibleRows.length} result(s) found`;
    }
}

// Status Updates
function initializeStatusUpdates() {
    const statusButtons = document.querySelectorAll('.status-btn');
    
    statusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reportId = this.dataset.reportId;
            const newStatus = this.dataset.status;
            
            if (confirm('Are you sure you want to update the status of this report?')) {
                updateReportStatus(reportId, newStatus, this);
            }
        });
    });
}

function updateReportStatus(reportId, status, button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';
    button.disabled = true;
    
    fetch('update_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            report_id: reportId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Report status updated successfully!', 'success');
            // Refresh the page or update the UI
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showAlert(data.message || 'Failed to update status', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while updating status', 'danger');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Utility Functions
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    
    return `${size.toFixed(2)} ${units[unitIndex]}`;
}

function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Export functionality
function exportTable(tableId, filename = 'export') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            let cellText = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + cellText + '"');
        }
        
        csv.push(row.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Print functionality
function printReport(reportId) {
    window.open(`print_report.php?id=${reportId}`, '_blank');
}

// Auto-save for forms
let autoSaveTimeout;
function initAutoSave(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                autoSaveForm(form);
            }, 2000);
        });
    });
}

function autoSaveForm(form) {
    const formData = new FormData(form);
    formData.append('auto_save', '1');
    
    fetch(form.action || '', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Draft saved automatically', 'info');
        }
    })
    .catch(error => {
        console.log('Auto-save failed:', error);
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + S to save forms
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        const activeForm = document.querySelector('form:focus-within');
        if (activeForm) {
            activeForm.submit();
        }
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        });
    }
});
