// Real-time M-Pesa status checker
let statusCheckInterval = null;

function startMpesaStatusCheck(billingId) {
    console.log('Starting M-Pesa status check for billing ID:', billingId);
    
    // Check every 5 seconds
    statusCheckInterval = setInterval(() => {
        checkMpesaStatus(billingId);
    }, 5000);
    
    // Stop after 2 minutes
    setTimeout(() => {
        stopMpesaStatusCheck();
    }, 120000);
}

function stopMpesaStatusCheck() {
    if (statusCheckInterval) {
        clearInterval(statusCheckInterval);
        statusCheckInterval = null;
        console.log('Stopped M-Pesa status check');
    }
}

async function checkMpesaStatus(billingId) {
    try {
        const response = await fetch(`${API_BASE}mpesa.php?action=check_status&billing_id=${billingId}`, {
            method: 'GET',
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success && data.status === 'paid') {
            console.log('Payment completed! Updating UI...');
            stopMpesaStatusCheck();
            
            // Update the billing table
            loadBilling();
            
            // Show success notification
            showToast('Payment Complete!', 'Bill has been marked as paid', 'success');
            
            // Close the M-Pesa modal
            hideModal();
        } else if (data.status === 'cancelled' || data.status === 'failed') {
            console.log('Payment failed:', data.message);
            stopMpesaStatusCheck();
            
            // Show error notification
            showToast('Payment Failed', data.message || 'Payment was not completed', 'error');
        }
    } catch (error) {
        console.error('Status check failed:', error);
    }
}
