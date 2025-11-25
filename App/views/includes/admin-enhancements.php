<?php
// Global Admin Enhancements - Toast Notifications, Validation, and UX Improvements
?>

<!-- Toast Notification System -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

<!-- Confirmation Dialog Modal -->
<div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900" id="confirmationTitle">Confirm Action</h3>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-sm text-gray-500" id="confirmationMessage">Are you sure you want to perform this action?</p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" id="confirmationCancel"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="confirmationConfirm"
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global Toast Notification System
function showToast(message, type = 'success', duration = 5000) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    
    const bgColor = type === 'success' ? 'bg-green-500' : 
                    type === 'error' ? 'bg-red-500' : 
                    type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
    const icon = type === 'success' ? 'fas fa-check-circle' : 
                type === 'error' ? 'fas fa-exclamation-circle' : 
                type === 'warning' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle';
    
    toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 transform transition-all duration-300 translate-x-full`;
    toast.innerHTML = `
        <i class="${icon}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Global Confirmation Dialog System
function showConfirmation(title, message, onConfirm, onCancel = null) {
    const modal = document.getElementById('confirmationModal');
    const titleEl = document.getElementById('confirmationTitle');
    const messageEl = document.getElementById('confirmationMessage');
    const confirmBtn = document.getElementById('confirmationConfirm');
    const cancelBtn = document.getElementById('confirmationCancel');
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    modal.classList.remove('hidden');
    
    // Remove existing event listeners
    const newConfirmBtn = confirmBtn.cloneNode(true);
    const newCancelBtn = cancelBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
    
    // Add new event listeners
    newConfirmBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        if (onConfirm) onConfirm();
    });
    
    newCancelBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        if (onCancel) onCancel();
    });
}

// Enhanced Form Validation
function validateField(field, errorId, customValidation = null) {
    const value = field.value.trim();
    const errorElement = document.getElementById(errorId);
    let isValid = true;
    let errorMessage = '';
    
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    } else if (field.type === 'email' && value && !isValidEmail(value)) {
        isValid = false;
        errorMessage = 'Please enter a valid email address';
    } else if (field.type === 'tel' && value && !isValidPhone(value)) {
        isValid = false;
        errorMessage = 'Please enter a valid phone number';
    } else if (customValidation) {
        const customResult = customValidation(value);
        if (!customResult.isValid) {
            isValid = false;
            errorMessage = customResult.message;
        }
    }
    
    if (isValid) {
        field.classList.remove('border-red-500');
        field.classList.add('border-gray-300');
        if (errorElement) {
            errorElement.classList.add('hidden');
        }
    } else {
        field.classList.remove('border-gray-300');
        field.classList.add('border-red-500');
        if (errorElement) {
            errorElement.textContent = errorMessage;
            errorElement.classList.remove('hidden');
        }
    }
    
    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}

// Enhanced Form Submission with Loading States
function enhanceFormSubmission(formId, options = {}) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate required fields
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            const errorId = field.getAttribute('aria-describedby');
            if (!validateField(field, errorId)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            showToast('Please fix the errors before submitting', 'error');
            return;
        }
        
        // Show loading state
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        }
        
        // Submit form
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (options.successCallback) {
                options.successCallback(data);
            } else {
                if (data.includes('successfully') || data.includes('created') || data.includes('updated')) {
                    showToast(options.successMessage || 'Operation completed successfully!', 'success');
                    if (options.redirectUrl) {
                        setTimeout(() => {
                            window.location.href = options.redirectUrl;
                        }, 1500);
                    }
                } else {
                    showToast(options.errorMessage || 'Operation failed. Please try again.', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    });
}

// Real-time validation for forms
function addRealTimeValidation(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const fields = form.querySelectorAll('input, select, textarea');
    fields.forEach(field => {
        const errorId = field.getAttribute('aria-describedby');
        if (errorId) {
            field.addEventListener('blur', () => validateField(field, errorId));
            field.addEventListener('input', () => {
                if (field.classList.contains('border-red-500')) {
                    validateField(field, errorId);
                }
            });
        }
    });
}

// Bulk Actions Enhancement
function enhanceBulkActions(containerId, options = {}) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const selectAllCheckbox = container.querySelector('.select-all-checkbox');
    const itemCheckboxes = container.querySelectorAll('.item-checkbox');
    const bulkActionBtn = container.querySelector('.bulk-action-btn');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionState();
        });
    }
    
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionState);
    });
    
    function updateBulkActionState() {
        const checkedItems = container.querySelectorAll('.item-checkbox:checked');
        if (bulkActionBtn) {
            bulkActionBtn.disabled = checkedItems.length === 0;
        }
    }
}

// Initialize enhancements when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add real-time validation to all forms with id
    const forms = document.querySelectorAll('form[id]');
    forms.forEach(form => {
        addRealTimeValidation(form.id);
    });
    
    // Initialize bulk actions
    const bulkContainers = document.querySelectorAll('[id$="-bulk-container"]');
    bulkContainers.forEach(container => {
        enhanceBulkActions(container.id);
    });
});
</script>

<style>
/* Enhanced form styling */
.border-red-500 {
    border-color: #ef4444 !important;
}

.border-green-500 {
    border-color: #10b981 !important;
}

/* Loading states */
.btn-loading {
    display: none;
}

.btn-loading.show {
    display: inline-block;
}

/* Toast animations */
@keyframes slideInRight {
    from {
        transform: translateX(100%);
    }
    to {
        transform: translateX(0);
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(100%);
    }
}

.toast-slide-in {
    animation: slideInRight 0.3s ease-out;
}

.toast-slide-out {
    animation: slideOutRight 0.3s ease-in;
}
</style>


