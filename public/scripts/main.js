document.addEventListener('DOMContentLoaded', () => {
    // Auto-hide notifications after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Mobile menu
    const menuIcon = document.querySelector(".display-mobile.fa-bars");
    const navList = document.querySelector("nav > div.container > ul");

    // Only execute if both elements actually exist on the page
    if (menuIcon && navList) {
        menuIcon.addEventListener("click", () => {
            if (navList.style.display === "block") {
                navList.style.display = "none";
            } else {
                navList.style.display = "block";
            }
        });
    }
});

// Global functions called by onclick in HTML

function buyGame(gameId) {
    const button = document.getElementById('buy-button');
    const messageSpan = document.getElementById('response-message');
    
    // Protection against error if the script does not find the button
    if (!button || !messageSpan) return;

    button.disabled = true;
    button.innerText = "Processing...";

    fetch('/buy', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ gameId: gameId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.classList.add('btn-disabled');
            button.innerText = "In Library";
            messageSpan.className = "msg-success";
            messageSpan.innerText = "Added successfully!";
            
            // Review Form Disclosure
            const mustOwnMsg = document.getElementById('must-own-msg');
            const hiddenReviewForm = document.getElementById('hidden-review-form');
            
            if (mustOwnMsg && hiddenReviewForm) {
                // We turn off the text, turn on the form (with smooth transition)
                mustOwnMsg.style.display = 'none';
                hiddenReviewForm.style.display = 'block';
                hiddenReviewForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } else {
            button.disabled = false;
            button.innerText = "Add to Library";
            messageSpan.className = "msg-error";
            messageSpan.innerText = data.error || "An error occurred";
        }
    })
    .catch(error => {
        console.error("Error:", error);
        button.disabled = false;
        button.innerText = "Add to Library";
        messageSpan.className = "msg-error";
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

// Load More Games (Store)
document.addEventListener('DOMContentLoaded', () => {
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
});