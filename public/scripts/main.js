import * as utils from './utils.js';
import * as game from './game.js';
import * as ui from './ui-handlers.js';
import * as filters from './filters.js';
import * as navi from './navigation.js';

// We pack dynamic elements into a separate function
function initDynamicContent() {
    filters.initCategoryLogic();

    // Store: Discover 4 games at a time
    utils.setupLoadMore('load-more-btn', '.game-card', 'hidden-game', 4);
    
    // Game Details: Discover 3 reviews at a time
    utils.setupLoadMore('load-more-reviews-btn', '.review-card', 'hidden-review', 3);

    // Admin: Users and Games (3 at a time)
    utils.setupLoadMore('load-more-users-btn', '.user-row', 'hidden-admin-row', 3);
utils.setupLoadMore('load-more-admin-games-btn', '.game-row', 'hidden-admin-row', 3);

    filters.restoreState();
}

document.addEventListener('DOMContentLoaded', () => {
    utils.initAlerts();
    utils.initMobileMenu();
    utils.initFileSizeProtection();
    utils.setupClearButton();
    
    // Initialization on first page load
    initDynamicContent();

    // Listener: clicking outside the dropdown area closes it
    document.addEventListener('click', ui.closeDropdownsOnClickOutside);
});

// We listen for the content reload event from our new AJAX navigation!
document.addEventListener('content-reloaded', () => {
    initDynamicContent();

    navi.animateTiles();
});

// We expose functions to Window so that HTML (onclick) can see them
window.showFlashMessage = utils.showFlashMessage;
window.copyGameLink = utils.copyGameLink;
window.buyGame = game.buyGame;
window.toggleLike = game.toggleLike;
window.toggleWishlist = game.toggleWishlist;
window.openPaymentModal = ui.openPaymentModal;
window.closePaymentModal = ui.closePaymentModal;
window.toggleSecurityForm = ui.toggleSecurityForm;
window.toggleReviewForm = ui.toggleReviewForm;
window.selectAvatar = ui.selectAvatar;
window.toggleEditMode = ui.toggleEditMode;
window.toggleDropdown = ui.toggleDropdown;
window.clearFilters = filters.clearFilters;
window.handleVisualSort = filters.handleVisualSort;
window.applyFilters = filters.applyFilters;