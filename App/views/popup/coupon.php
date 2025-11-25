<!--<script src="https://cdn.commoninja.com/sdk/latest/commonninja.js" defer></script>-->
<!--<div class="commonninja_component pid-a2b5812a-6144-48c4-b1e2-d8960efc6473"></div>-->
<script>
// Wait until the page loads
window.addEventListener('load', function() {
    // Select the Common Ninja ribbon div
    const ribbon = document.querySelector('.commonninja-ribbon');

    // Remove it completely
    if (ribbon) {
        ribbon.remove();
    }

    // Or, alternatively, hide it instead of removing
    // if (ribbon) {
    //     ribbon.style.display = 'none';
    // }
});
</script>
<script>
// Function to remove the ribbon
function removeNinjaRibbon() {
    const ribbon = document.querySelector('.commonninja-ribbon');
    if (ribbon) {
        ribbon.remove();
    }
}

// Run once after page load
window.addEventListener('load', removeNinjaRibbon);

// Keep checking in case Common Ninja injects it later
const observer = new MutationObserver(removeNinjaRibbon);
observer.observe(document.body, { childList: true, subtree: true });
</script>



<!-- Elfsight Sales Notification | Untitled Sales Notification -->
<!--<script src="https://elfsightcdn.com/platform.js" async></script>-->
<!--<div class="elfsight-app-49d81970-dca9-4efe-bf79-fbcff8234524" data-elfsight-app-lazy></div>-->