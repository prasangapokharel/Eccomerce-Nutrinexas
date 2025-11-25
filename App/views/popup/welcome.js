// welcome.js

(function () {
  // Check if popup was closed before
  if (document.cookie.includes("welcome_closed=true")) {
    return;
  }

  // Create overlay
  const overlay = document.createElement("div");
  overlay.style.position = "fixed";
  overlay.style.inset = "0";
  overlay.style.background = "rgba(0,0,0,0.6)";
  overlay.style.display = "flex";
  overlay.style.alignItems = "center";
  overlay.style.justifyContent = "center";
  overlay.style.zIndex = "9999";
  overlay.id = "welcomePopup";

  // Create popup box
  const popup = document.createElement("div");
  popup.style.position = "relative";
  popup.style.background = "#0A3167";
  popup.style.borderRadius = "12px";
  popup.style.padding = "10px";
  popup.style.maxWidth = "400px";
  popup.style.boxShadow = "0 4px 12px rgba(0,0,0,0.3)";

  // Add image
  const img = document.createElement("img");
  img.src = "https://qkjsnpejxzujoaktpgpq.supabase.co/storage/v1/object/public/nutrinexas/welocme.gif";
  img.alt = "Welcome";
  img.style.width = "100%";
  img.style.height = "auto";
  img.style.borderRadius = "8px";

  // Close button
  const closeBtn = document.createElement("div");
  closeBtn.style.position = "absolute";
  closeBtn.style.top = "6px";
  closeBtn.style.right = "6px";
  closeBtn.style.cursor = "pointer";
  closeBtn.style.width = "28px";
  closeBtn.style.height = "28px";
  closeBtn.style.background = "#C5A572";
  closeBtn.style.borderRadius = "50%";
  closeBtn.style.display = "flex";
  closeBtn.style.alignItems = "center";
  closeBtn.style.justifyContent = "center";
  closeBtn.style.boxShadow = "0 2px 5px rgba(0,0,0,0.2)";

  closeBtn.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" 
      viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
      style="width:18px; height:18px; stroke:black;">
      <path stroke-linecap="round" stroke-linejoin="round"
        d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
    </svg>
  `;

  // Close logic
  closeBtn.addEventListener("click", () => {
    overlay.remove();
    document.cookie = "welcome_closed=true; path=/; max-age=" + 60 * 60 * 24 * 7; // 7 days
  });

  // Build DOM
  popup.appendChild(closeBtn);
  popup.appendChild(img);
  overlay.appendChild(popup);
  document.body.appendChild(overlay);
})();
