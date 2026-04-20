import { showFlashMessage } from './utils.js';

// Buy Game Logic
export async function buyGame(gameId) {
    try {
        const response = await fetch('/buy', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `game_id=${gameId}`
        });

        const result = await response.json();
        const msgDiv = document.getElementById('response-message');

        if (result.success) {
            showFlashMessage('Game added to library successfully!', 'success');

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
            const errorText = result.message || result.error || "Unknown server error";
            showFlashMessage(errorText, 'error');
        }
    } catch (error) {
        showFlashMessage('Server communication error.', 'error');
    }
}

// Toggle Review Like (Helpful) 
export async function toggleLike(buttonElement, reviewId) {
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

// Wishlist Toggle Action
export function toggleWishlist(gameId) {
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