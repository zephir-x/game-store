// Global Utilities & UI

// Auto-hide notifications (after 5 seconds)
export function initAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
}

// Mobile menu 
export function initMobileMenu() {
    const menuToggle = document.getElementById("mobile-menu-toggle");
    const menuWrapper = document.getElementById("nav-menu-wrapper");

    if (menuToggle && menuWrapper) {
        menuToggle.addEventListener("click", () => {
            // We switch the class that is responsible for expanding the menu
            menuWrapper.classList.toggle("open");
        });
    }
}

// Protection against files that are too large
export function initFileSizeProtection() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const maxSize = 50 * 1024 * 1024; // limit 50MB
            if (this.files && this.files[0]) {
                if (this.files[0].size > maxSize) {
                    alert('File is too large! Maximum allowed size is 50MB.');
                    this.value = ''; // clears the selected file
                }
            }
        });
    }); 
}

// Smooth exit for Clear button
export function setupClearButton() {
    const clearBtn = document.querySelector('.btn-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            clearBtn.classList.add('hiding');
            
            // Wait 250ms (until the animation ends) and only then redirect to the home page
            setTimeout(() => {
                window.location.href = clearBtn.href;
            }, 250); 
        });
    }
}

// Load More logic
export function setupLoadMore(buttonId, itemSelector, hiddenClass, chunkSize) {
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

// Function for displaying messages "on the fly"
export function showFlashMessage(message, type = 'success') {
    // We create an alert element
    const alert = document.createElement('div');
    
    // We add classes: the base .alert, the color-coded one, and our new .alert-toast
    alert.className = `alert alert-${type} alert-toast toast-slide-down`;
    
    // We select the icon
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-circle-xmark';
    
    alert.innerHTML = `
        <i class="fa-solid ${icon}"></i>
        <span>${message}</span>
    `;

    // We insert it directly into the body (so that it is at the very top of the DOM)
    document.body.appendChild(alert);

    // Automatic deletion after 5 seconds
    setTimeout(() => {
        alert.style.transition = 'all 0.5s cubic-bezier(0.47, 0, 0.74, 0.71)';
        alert.style.top = '-100px';
        alert.style.opacity = '0';
        
        setTimeout(() => alert.remove(), 500);
    }, 5000);
}

// Copy Link function
export function copyGameLink(btn) {
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