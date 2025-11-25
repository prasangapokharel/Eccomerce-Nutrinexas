<?php
// Default values for modal content - these can be overridden before including this file
// Example: $modalMediaType = 'video'; $modalMediaSrc = 'https://www.w3schools.com/html/mov_bbb.mp4'; $modalVideoPoster = 'https://www.w3schools.com/html/pic_trulli.jpg';
$modalMediaType = $modalMediaType ?? 'video'; // 'image' or 'video'
$modalMediaSrc = $modalMediaSrc ?? 'https://cdn.shopify.com/videos/c/o/v/32767d7a5a304a198cad6887b663311c.mp4';
$modalVideoPoster = $modalVideoPoster ?? 'https://cdn.shopify.com/s/files/1/0233/6459/9885/files/70eddbcd09a36c63f7c3b1ade0c52fe0276a52d7.png?v=1745337646?height=200&width=300'; // Placeholder for video poster
?>
<div id="arriesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden transition-opacity duration-300 ease-in-out opacity-0">
    <div class="bg-white rounded-2xl shadow-lg p-6 w-full max-w-md transform scale-95 opacity-0 transition-all duration-300 ease-in-out" id="arriesModalContent">
        <div class="flex justify-end mb-4">
            <button id="closeArriesModal" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="text-center mb-6">
            <?php if ($modalMediaType === 'video'): ?>
                <video controls autoplay class="mx-auto mb-4 w-full h-auto object-contain rounded-2xl" poster="<?= htmlspecialchars($modalVideoPoster) ?>">
                    <source src="<?= htmlspecialchars($modalMediaSrc) ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            <?php else: ?>
                <img src="<?= htmlspecialchars($modalMediaSrc) ?>" alt="Product Authenticator" class="mx-auto mb-4 w-32 h-32 object-contain rounded-2xl">
            <?php endif; ?>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Authorize Your Product</h2>
            <p class="text-gray-600">Ensure the authenticity of your purchase.</p>
        </div>
        <div class="flex justify-center">
            <a href="https://wellversed.in/pages/authenticator" target="_blank" class="clip bg-primary hover:bg-primary text-white font-semibold py-3 px-6 rounded-xl transition-colors duration-300 flex items-center justify-center">
             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
</svg>

                Check Now
            </a>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('arriesModal');
        const modalContent = document.getElementById('arriesModalContent');
        const closeButton = document.getElementById('closeArriesModal');

        // Function to open the modal, exposed globally
        window.openArriesModal = function() {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10); // Small delay to allow CSS transition
        }

        // Function to close the modal
        function closeArriesModal() {
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300); // Match CSS transition duration
        }

        // Event listeners
        closeButton.addEventListener('click', closeArriesModal);

        // Close modal when clicking outside the content
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeArriesModal();
            }
        });

        // For demonstration: Add a button to open it (you can remove this in production)
        const demoButton = document.createElement('button');
 demoButton.innerHTML = `
  Authenticator
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text pulse inline-block ml-2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
  </svg>
`;
demoButton.className = 'fixed bottom-4 left-4 bg-accent text-white px-4 py-2 clip shadow-lg z-50';
        demoButton.onclick = window.openArriesModal;
        document.body.appendChild(demoButton);
    });
</script>
