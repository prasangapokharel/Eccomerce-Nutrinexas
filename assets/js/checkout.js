// Checkout functionality
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkout-form');
    const placeOrderBtn = document.getElementById('place-order-btn');
    const processingModal = document.getElementById('processing-modal');
    const processingOverlay = document.getElementById('processing-overlay');

    if (checkoutForm && placeOrderBtn) {
        placeOrderBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validate form before submitting
            if (!validateCheckoutForm()) {
                return;
            }
            
            // Show processing modal
            showProcessingModal();
            
            // Disable the button to prevent double submission
            placeOrderBtn.disabled = true;
            placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Prepare form data
            const formData = new FormData(checkoutForm);
            
            // Send AJAX request
            fetch(window.location.origin + '/checkout/process', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Check content type
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Response is not JSON');
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                hideProcessingModal();
                
                if (data.success) {
                    // Show success message
                    showSuccessMessage(data.message || 'Order placed successfully!');
                    
                    // Redirect to success page after a short delay
                    setTimeout(() => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.href = window.location.origin + '/orders';
                        }
                    }, 2000);
                } else {
                    // Show error message
                    showErrorMessage(data.message || 'Failed to process order');
                    resetPlaceOrderButton();
                }
            })
            .catch(error => {
                console.error('Checkout error:', error);
                hideProcessingModal();
                showErrorMessage('An error occurred while processing your order. Please try again.');
                resetPlaceOrderButton();
            });
        });
    }

    // Form validation
    function validateCheckoutForm() {
        const requiredFields = [
            'recipient_name',
            'phone',
            'address_line1',
            'city',
            'state',
            'postal_code',
            'payment_method_id'
        ];

        let isValid = true;
        let firstErrorField = null;

        requiredFields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                const value = field.type === 'radio' ? 
                    document.querySelector(`[name="${fieldName}"]:checked`)?.value : 
                    field.value.trim();

                if (!value) {
                    markFieldAsError(field);
                    isValid = false;
                    if (!firstErrorField) {
                        firstErrorField = field;
                    }
                } else {
                    markFieldAsValid(field);
                }
            }
        });

        if (!isValid && firstErrorField) {
            firstErrorField.focus();
            showErrorMessage('Please fill in all required fields');
        }

        return isValid;
    }

    function markFieldAsError(field) {
        field.classList.add('error');
        field.style.borderColor = '#dc3545';
    }

    function markFieldAsValid(field) {
        field.classList.remove('error');
        field.style.borderColor = '';
    }

    function showProcessingModal() {
        if (processingModal) {
            processingModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        } else {
            // Create modal if it doesn't exist
            createProcessingModal();
        }
    }

    function hideProcessingModal() {
        if (processingModal) {
            processingModal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    function createProcessingModal() {
        const modal = document.createElement('div');
        modal.id = 'processing-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        `;

        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        `;

        modalContent.innerHTML = `
            <div style="margin-bottom: 20px;">
                <div style="width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div>
            </div>
            <h3 style="margin: 0 0 10px 0; color: #333;">Processing your order...</h3>
            <p style="margin: 0; color: #666;">Please don't close this window</p>
        `;

        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);

        modal.appendChild(modalContent);
        document.body.appendChild(modal);
    }

    function resetPlaceOrderButton() {
        if (placeOrderBtn) {
            placeOrderBtn.disabled = false;
            placeOrderBtn.innerHTML = '<i class="fas fa-credit-card"></i> Place Order';
        }
    }

    function showSuccessMessage(message) {
        showNotification(message, 'success');
    }

    function showErrorMessage(message) {
        showNotification(message, 'error');
    }

    function showNotification(message, type) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.checkout-notification');
        existingNotifications.forEach(notification => notification.remove());

        const notification = document.createElement('div');
        notification.className = 'checkout-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            z-index: 10000;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-out;
        `;

        if (type === 'success') {
            notification.style.backgroundColor = '#28a745';
            notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        } else {
            notification.style.backgroundColor = '#dc3545';
            notification.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        }

        // Add slide-in animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }

    // Add slide-out animation
    const slideOutStyle = document.createElement('style');
    slideOutStyle.textContent = `
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(slideOutStyle);

    // Handle form field changes to remove error styling
    const formFields = checkoutForm?.querySelectorAll('input, select, textarea');
    if (formFields) {
        formFields.forEach(field => {
            field.addEventListener('input', function() {
                markFieldAsValid(this);
            });
            
            field.addEventListener('change', function() {
                markFieldAsValid(this);
            });
        });
    }
});
