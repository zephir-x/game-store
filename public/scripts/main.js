// Select hamburger menu icon (mobile navigation button)
const menuIcon = document.querySelector(".display-mobile.fa-bars");

// Select navigation list inside navbar
const navList = document.querySelector("nav > div.container > ul");

// Toggle navigation visibility on click
menuIcon.addEventListener("click", () => {

  // Check current display state
  if (navList.style.display === "block") {
    navList.style.display = "none"; // Hide menu
  } else {
    navList.style.display = "block"; // Show menu
  }
});

// Function to handle game purchase
function buyGame(gameId) {
    // Button responsible for purchasing (we block it to prevent the user from spamming clicks)
    const button = document.getElementById('buy-button');
    
    // Place for user messages (e.g., success / error)
    const messageSpan = document.getElementById('response-message');
    
    // Block the button to prevent multiple submissions
    button.disabled = true;
    button.innerText = "Processing...";

    // Send request to backend for game purchase
    fetch('/buy', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        // Pass the game ID to the backend
        body: JSON.stringify({ gameId: gameId })
    })
    .then(response => response.json())
    .then(data => {
        // If backend returns success
        if (data.success) {
            // Update UI - button disabled and text changed
            button.classList.add('btn-disabled');
            button.innerText = "In Library";

            // Inform user of success
            messageSpan.className = "msg-success";
            messageSpan.innerText = "Added successfully!";
        } else {
            // If something went wrong on the backend logic side
            button.disabled = false;
            button.innerText = "Add to Library";

            // Display error returned by API or fallback
            messageSpan.className = "msg-error";
            messageSpan.innerText = data.error || "An error occurred";
        }
    })
    .catch(error => {
        // Handle network errors (no connection, timeout etc.)
        console.error("Error:", error);

        // Restore the button to its usable state
        button.disabled = false;
        button.innerText = "Add to Library";

        // Inform user of network issue
        messageSpan.className = "msg-error";
        messageSpan.innerText = "Connection error";
    });
}