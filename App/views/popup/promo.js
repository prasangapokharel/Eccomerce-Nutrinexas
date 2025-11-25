// promo.js
document.addEventListener("DOMContentLoaded", function() {
    // Check if promo was already shown
    if (document.cookie.indexOf("promoShown=true") === -1) {
        // Delay 5 seconds before showing
        setTimeout(showPromo, 5000);
    }

    function showPromo() {
        // Create overlay
        const overlay = document.createElement("div");
        overlay.id = "promoOverlay";
        overlay.style.position = "fixed";
        overlay.style.top = 0;
        overlay.style.left = 0;
        overlay.style.width = "100vw";
        overlay.style.height = "100vh";
        overlay.style.backgroundColor = "rgba(0,0,0,0.8)";
        overlay.style.display = "flex";
        overlay.style.justifyContent = "center";
        overlay.style.alignItems = "center";
        overlay.style.zIndex = 9999;
        overlay.style.transition = "opacity 0.3s ease";

        // Create image
        const img = document.createElement("img");
        img.src = "https://qkjsnpejxzujoaktpgpq.supabase.co/storage/v1/object/public/nutrinexas/nxrrr.jpg";
        img.style.maxWidth = "90%";
        img.style.maxHeight = "90%";
        img.style.objectFit = "contain";
        img.style.borderRadius = "5%"; // optional
        img.alt = "Promo";

        overlay.appendChild(img);
        document.body.appendChild(overlay);

        // Set cookie so promo won't show again
        document.cookie = "promoShown=true; max-age=" + 60*60*24; // 1 day

        // Auto close after 4 seconds
        setTimeout(() => {
            overlay.style.opacity = 0;
            setTimeout(() => {
                document.body.removeChild(overlay);
            }, 300); // fade out transition
        }, 4000);

        // Optional: close on click
        overlay.addEventListener("click", () => {
            overlay.style.opacity = 0;
            setTimeout(() => {
                document.body.removeChild(overlay);
            }, 300);
        });
    }
});
