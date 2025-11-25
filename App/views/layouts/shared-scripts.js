// Shared JavaScript for NutriNexus
// Clean, production-ready code without animations

// Order status update functionality
function updateOrderStatus(orderId, newStatus, note = '') {
    if (confirm(`Mark order #${orderId} as ${newStatus}?`)) {
        fetch(window.location.origin + '/staff/updateOrderStatus', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}&status=${newStatus}&note=${encodeURIComponent(note)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Order status updated successfully!');
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to update order status'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating order status. Please try again.');
        });
    }
}

// Curior order status update
function updateDeliveryStatus(orderId, status) {
    if (confirm(`Mark order #${orderId} as ${status}?`)) {
        fetch(window.location.origin + '/curior/updateOrderStatus', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Order status updated successfully!');
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to update order status'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating order status. Please try again.');
        });
    }
}

// Alert sound for new orders (no animations)
function playAlertSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);
        
        gainNode.gain.setValueAtTime(0.2, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    } catch (error) {
        console.log('Audio not supported');
    }
}

// Check for new orders (staff dashboard)
function checkForNewOrders() {
    fetch(window.location.origin + '/staff/getOrderCount')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count > window.lastOrderCount) {
                playAlertSound();
                window.lastOrderCount = data.count;
                
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('New Order!', {
                        body: 'A new order needs to be packed.',
                        icon: '📦'
                    });
                }
            }
        })
        .catch(error => console.error('Error checking orders:', error));
}

// Auto-hide flash messages (no animations)
function autoHideFlashMessages() {
    const flashMessages = document.querySelectorAll('.bg-green-100, .bg-red-100');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 500);
        }, 5000);
    });
}

// Initialize notification permission
function initNotifications() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    autoHideFlashMessages();
    initNotifications();
    
    // Set initial order count for staff dashboard
    if (typeof window.lastOrderCount === 'undefined') {
        window.lastOrderCount = 0;
    }
    
    // Start checking for new orders every 30 seconds (staff only)
    if (window.location.pathname.includes('/staff/dashboard')) {
        setInterval(checkForNewOrders, 30000);
    }
});
