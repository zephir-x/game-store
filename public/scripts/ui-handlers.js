import { buyGame } from './game.js';
import { showFlashMessage } from './utils.js';

// Payment Modal Logic
let currentGameToBuy = null;

export function openPaymentModal(gameId, price) {
    currentGameToBuy = gameId;
    document.getElementById('modal-price').innerText = price + ' PLN';
    
    const modal = document.getElementById('payment-modal');
    const modalContent = document.getElementById('payment-modal-content');
    const msgDiv = document.getElementById('response-message');
    
    // We clear any previous messages in the modal
    if (msgDiv) msgDiv.innerHTML = '';

    modal.classList.remove('hidden');
    
    // Short delay, so the CSS animation can take effect after removing 'hidden'
    setTimeout(() => { 
        modal.style.opacity = '1'; 
        modalContent.style.transform = 'translateY(0)';
    }, 10);
    
    // We assign the game ID to the Pay Now button
    document.getElementById('confirm-payment-btn').onclick = function() {
        const gameIdToBuy = currentGameToBuy;
        closePaymentModal(false);
        buyGame(gameIdToBuy); // we launch the purchase with the saved ID
    };
}

export function closePaymentModal(isCancelled = true) {
    const modal = document.getElementById('payment-modal');
    const modalContent = document.getElementById('payment-modal-content');
    const msgDiv = document.getElementById('response-message');
    
    modal.style.opacity = '0';
    modalContent.style.transform = 'translateY(20px)';
    
    if (isCancelled && msgDiv) {
        showFlashMessage('Purchase cancelled by user.', 'error');
    }

    setTimeout(() => { 
        modal.classList.add('hidden'); 
    }, 300); // we wait until the fade-out animation ends
    
    currentGameToBuy = null;
}

// Security Zone Forms Toggle
export function toggleSecurityForm(targetFormType) {
    const container = document.getElementById('security-forms-container');
    const allForms = document.querySelectorAll('.sec-form');
    
    // We always hide all forms at the beginning
    allForms.forEach(form => form.classList.add('hidden'));
    
    if (targetFormType === 'none') {
        container.classList.add('hidden'); // close all
        return;
    }
    
    // Show container and corresponding form with animation
    container.classList.remove('hidden');
    const targetForm = document.getElementById(`sec-form-${targetFormType}`);
    
    if (targetForm) {
        targetForm.classList.remove('hidden');
        targetForm.classList.remove('fade-in-card'); // reset animation
        void targetForm.offsetWidth; // force reflow for animation restart
        targetForm.classList.add('fade-in-card');
    }
}

// Toggle Review Form
export function toggleReviewForm() {
    const container = document.getElementById('review-form-container');
    if (!container) return;
    container.classList.toggle('hidden');
    if (!container.classList.contains('hidden')) container.classList.add('fade-in-card');
}

export function selectAvatar(filename) {
    document.querySelectorAll('.avatar-option').forEach(img => {
        img.classList.remove('selected');
    });
    
    const selectedImg = document.querySelector(`.avatar-option[src="/public/resources/avatars/${filename}"]`);
    if(selectedImg) selectedImg.classList.add('selected');
    
    const avatarInput = document.getElementById('avatar-input');
    if(avatarInput) avatarInput.value = filename;
}

export function toggleEditMode() {
    const view = document.getElementById('profile-view');
    const edit = document.getElementById('profile-edit');
    const secZone = document.querySelector('.security-zone');
    
    if (view && edit) {
        if (view.classList.contains('hidden')) {
            // We return to the profile view
            edit.classList.add('hidden');
            edit.classList.remove('fade-in-card');
            
            view.classList.remove('hidden');
            view.classList.add('fade-in-card');

            if (secZone) {
                secZone.classList.remove('hidden');
                void secZone.offsetWidth; // force reflow for animation restart
                secZone.classList.add('fade-in-card');
            }
        } else {
            // Let's move on to editing
            view.classList.add('hidden');
            view.classList.remove('fade-in-card');
            
            edit.classList.remove('hidden');
            edit.classList.add('fade-in-card');

            // We hide the Security Zone
            if (secZone) {
                secZone.classList.add('hidden');
                secZone.classList.remove('fade-in-card');
            }
        }
    }
}