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

    // 3. Load More (Games or Reviews)
    function setupLoadMore(buttonId, itemSelector, hiddenClass, chunkSize) {
        const btn = document.getElementById(buttonId);
        if (!btn) return;

        btn.addEventListener('click', () => {
            // We are only looking for those elements that are still hidden
            const hiddenItems = document.querySelectorAll(`${itemSelector}.${hiddenClass}`);
            
            // We cut out only a specific chunk from them (e.g., the first 3 or 4)
            const itemsToReveal = Array.from(hiddenItems).slice(0, chunkSize);
            
            itemsToReveal.forEach((item, index) => {
                item.classList.remove(hiddenClass);
                item.classList.add('fade-in-card');
                item.style.animationDelay = `${index * 0.1}s`;
                
                // We remove the animation block after entering (so that :hover works again)
                setTimeout(() => {
                    item.classList.remove('fade-in-card');
                    item.style.animationDelay = '';
                }, (index * 100) + 800);
            });
            
            // If there are fewer or equal hidden elements then we've revealed everything. We hide the button
            if (hiddenItems.length <= chunkSize) {
                btn.style.display = 'none';
            }
        });
    }

    // Store: Discover 4 games at a time
    setupLoadMore('load-more-btn', '.game-card', 'hidden-game', 4);
    
    // Game Details: Discover 3 reviews at a time
    setupLoadMore('load-more-reviews-btn', '.review-card', 'hidden-review', 3);

    // Admin: Users and Games (3 at a time)
    setupLoadMore('load-more-users-btn', '.admin-table-row', 'hidden-admin-row', 3);
    setupLoadMore('load-more-admin-games-btn', '.admin-table-row', 'hidden-admin-row', 3);

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

// Buy Game Logic
async function buyGame(gameId) {
    try {
        const response = await fetch('/buy', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `game_id=${gameId}`
        });

        const result = await response.json();
        const msgDiv = document.getElementById('response-message');

        if (result.success) {
            // We're changing the purchase button to a large "Already in Library" button
            const buySection = document.getElementById('buy-section');
            if (buySection) {
                buySection.innerHTML = `
                    <button disabled class="btn-primary btn-block btn-disabled fade-in-card" style="padding: 20px; font-size: 1.1rem; margin-bottom: 20px;">
                        <i class="fa-solid fa-check btn-icon-margin"></i> Already in Library
                    </button>
                `;
            }

            // We hide the Wishlist button (since we already have the game)
            const wishlistBtn = document.getElementById('wishlist-btn');
            if (wishlistBtn) wishlistBtn.style.display = 'none';

            // We activate the reviews section
            const reviewsHeader = document.querySelector('.reviews-header');
            if (reviewsHeader) {
                // We remove the text "Own this game to review" with the lock icon
                const lockSpan = reviewsHeader.querySelector('.text-muted');
                if (lockSpan) lockSpan.remove();

                // We are adding a "Write a Review" button
                const reviewBtnHTML = `
                    <button onclick="toggleReviewForm()" class="btn-outline fade-in-card">
                        <i class="fa-solid fa-plus btn-icon-margin"></i> Write a Review
                    </button>
                `;
                reviewsHeader.insertAdjacentHTML('beforeend', reviewBtnHTML);
            }

            // Smooth scrolling to the reviews section
            const reviewsSection = document.querySelector('.reviews-section');
            if (reviewsSection) {
                reviewsSection.scrollIntoView({ behavior: 'smooth' });
                
                // We wait 600ms (until it finishes scrolling) and automatically slide out the form
                setTimeout(() => toggleReviewForm(), 600);
            }

        } else {
            msgDiv.innerHTML = `<span class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> ${result.message}</span>`;
        }
    } catch (error) {
        console.error("Error during purchase:", error);
    }
}

function toggleEditMode() {
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

// Security Zone Forms Toggle
function toggleSecurityForm(targetFormType) {
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
function toggleReviewForm() {
    const container = document.getElementById('review-form-container');
    if (container) {
        if (container.classList.contains('hidden')) {
            container.classList.remove('hidden');
            void container.offsetWidth;
            container.classList.add('fade-in-card');
        } else {
            container.classList.add('hidden');
            container.classList.remove('fade-in-card');
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

// Toggle Review Like (Helpful) 
async function toggleLike(buttonElement, reviewId) {
    // Double-click protection (Anti-spam)
    if (buttonElement.disabled) return;
    buttonElement.disabled = true;

    try {
        const response = await fetch('/toggle-like', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reviewId: reviewId })
        });

        const data = await response.json();
        buttonElement.disabled = false; // we unlock the button after the server responds

        if (data.success) {
            const icon = buttonElement.querySelector('i');
            const countSpan = buttonElement.querySelector('.like-count');
            let currentCount = parseInt(countSpan.innerText);

            if (data.action === 'added') {
                // We light the button and increase the counter
                buttonElement.classList.add('active');
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
                countSpan.innerText = currentCount + 1;
            } else if (data.action === 'removed') {
                // We dim the button and decrease the counter
                buttonElement.classList.remove('active');
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
                countSpan.innerText = currentCount - 1;
            }
        } else {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert(data.error || "Could not process your request.");
            }
        }
    } catch (error) {
        console.error("Error toggling like:", error);
        buttonElement.disabled = false;
    }
}

// Copy Link function
function copyGameLink(btn) {
    const url = window.location.href;
    
    // Save to clipboard
    navigator.clipboard.writeText(url).then(() => {
        const originalHtml = btn.innerHTML;
        
        // Change button appearance to success
        btn.innerHTML = '<i class="fa-solid fa-check btn-icon-margin"></i> Copied!';
        btn.style.color = 'var(--primary)';
        btn.style.borderColor = 'var(--primary)';
        
        // Return to original state after 2 seconds
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.style.color = '';
            btn.style.borderColor = '';
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy text: ', err);
    });
}