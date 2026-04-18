document.addEventListener('DOMContentLoaded', () => {
    // 1. Auto-hide notifications after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // 2. Mobile menu (Safety check fixed)
    const menuIcon = document.querySelector(".display-mobile.fa-bars");
    const navList = document.querySelector("nav > div.container > ul");

    if (menuIcon && navList) {
        menuIcon.addEventListener("click", () => {
            navList.style.display = navList.style.display === "block" ? "none" : "block";
        });
    }

    // 3. Load More Games (Store)
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', () => {
            const hiddenGames = document.querySelectorAll('.hidden-game');
            hiddenGames.forEach((game, index) => {
                game.classList.remove('hidden-game');
                game.classList.add('fade-in-card');
                game.style.animationDelay = `${index * 0.1}s`; 
            });
            loadMoreBtn.style.display = 'none';
        });
    }

    // 4. Protection against files that are too large
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const maxSize = 2 * 1024 * 1024; // limit 2MB
            if (this.files && this.files[0]) {
                if (this.files[0].size > maxSize) {
                    alert('File is too large! Maximum allowed size is 2MB.');
                    this.value = ''; // clears the selected file
                }
            }
        });
    });
});

// Global Functions (Called from HTML)
function buyGame(gameId) {
    const button = document.getElementById('buy-button');
    const messageSpan = document.getElementById('response-message');
    
    if (!button || !messageSpan) return;

    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin btn-icon-margin"></i> Processing...';

    fetch('/buy', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ gameId: gameId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update Button
            button.classList.add('btn-disabled');
            button.innerText = "Already in Library";
            
            // Update Message with proper formatting
            messageSpan.className = "buy-response-msg highlight-green fw-bold";
            messageSpan.innerHTML = '<i class="fa-solid fa-check"></i> Added successfully!';
            
            // Review Form Disclosure
            const mustOwnMsg = document.getElementById('must-own-msg');
            const hiddenReviewForm = document.getElementById('hidden-review-form');
            
            if (mustOwnMsg && hiddenReviewForm) {
                mustOwnMsg.classList.add('hidden'); // hide the lock message
                hiddenReviewForm.classList.remove('hidden'); // show the form
                hiddenReviewForm.classList.add('fade-in-card'); // add nice animation
                
                // Smooth scroll to the newly revealed form
                setTimeout(() => {
                    hiddenReviewForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }
        } else {
            // Revert on error
            button.disabled = false;
            button.innerHTML = '<i class="fa-solid fa-cart-shopping btn-icon-margin"></i> Get Now';
            messageSpan.className = "buy-response-msg alert-error"; // Reusing our error styling
            messageSpan.innerText = data.error || "An error occurred";
        }
    })
    .catch(error => {
        console.error("Error:", error);
        button.disabled = false;
        button.innerHTML = '<i class="fa-solid fa-cart-shopping btn-icon-margin"></i> Get Now';
        messageSpan.className = "buy-response-msg alert-error";
        messageSpan.innerText = "Connection error";
    });
}

function toggleEditMode() {
    const view = document.getElementById('profile-view');
    const edit = document.getElementById('profile-edit');
    if (view && edit) {
        view.classList.toggle('hidden');
        edit.classList.toggle('hidden');
    }
}

function selectAvatar(filename) {
    document.querySelectorAll('.avatar-option').forEach(img => {
        img.classList.remove('selected');
    });
    
    const selectedImg = document.querySelector(`.avatar-option[src="/public/resources/avatars/${filename}"]`);
    if(selectedImg) selectedImg.classList.add('selected');
    
    const avatarInput = document.getElementById('avatar-input');
    if(avatarInput) avatarInput.value = filename;
}