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

    // 5. Smooth exit for Clear button
    const clearBtn = document.querySelector('.btn-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            clearBtn.classList.add('hiding');
            
            // Wait 400ms (until the animation ends) and only then redirect to the home page
            setTimeout(() => {
                window.location.href = clearBtn.href;
            }, 250); 
        });
    }
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
            
            // If purchased successfully, hide the Wishlist button (if present)
            const wishlistBtn = document.getElementById('wishlist-btn');
            if (wishlistBtn) wishlistBtn.style.display = 'none';

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
        if (view.classList.contains('hidden')) {
            // We return to the profile view
            edit.classList.add('hidden');
            edit.classList.remove('fade-in-card');
            
            view.classList.remove('hidden');
            view.classList.add('fade-in-card');
        } else {
            // Let's move on to editing
            view.classList.add('hidden');
            view.classList.remove('fade-in-card');
            
            edit.classList.remove('hidden');
            edit.classList.add('fade-in-card');
        }
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

// Wishlist Toggle Action
function toggleWishlist(gameId) {
    const btn = document.getElementById('wishlist-btn');
    const icon = btn.querySelector('i');
    
    // Multi-click protection
    if (btn.disabled) return;
    btn.disabled = true;

    fetch('/toggle-wishlist', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ gameId: gameId })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        
        if (data.success) {
            if (data.action === 'added') {
                btn.classList.add('active');
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
                btn.innerHTML = '<i class="fa-solid fa-heart btn-icon-margin"></i> On Wishlist';
            } else if (data.action === 'removed') {
                btn.classList.remove('active');
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
                btn.innerHTML = '<i class="fa-regular fa-heart btn-icon-margin"></i> Add to Wishlist';
            }
        } else {
            // If the user is not logged in
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert(data.error || "Something went wrong!");
            }
        }
    })
    .catch(error => {
        console.error("Error:", error);
        btn.disabled = false;
    });
}