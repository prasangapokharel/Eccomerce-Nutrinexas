<?php ob_start(); ?>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">Return Policy</h1>
        
        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <p class="text-gray-700 mb-4">
                At NutriNexus, we want you to be completely satisfied with your purchase. This return policy outlines the terms and conditions for returning products.
            </p>
            <p class="text-sm text-gray-500">
                <strong>Last Updated:</strong> <?= date('F j, Y') ?>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Return Eligibility</h2>
            
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <p class="text-gray-800 font-semibold">
                    Returns are only accepted within 7 days of delivery date.
                </p>
            </div>
            
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Eligible for Return:</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li>Defective or damaged products</li>
                        <li>Wrong product received</li>
                        <li>Products in original, unopened packaging</li>
                        <li>Products with all original tags and labels attached</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Not Eligible for Return:</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li>Products used or opened (unless defective)</li>
                        <li>Products without original packaging</li>
                        <li>Products returned after 7 days of delivery</li>
                        <li>Products damaged due to customer misuse</li>
                        <li>Perishable items or items with expired dates</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Return Process</h2>
            
            <div class="space-y-4">
                <div class="border-l-4 border-primary pl-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Step 1: Request Return</h3>
                    <p class="text-gray-600">Contact us within 7 days of delivery to request a return. All returns require approval from our team.</p>
                </div>
                
                <div class="border-l-4 border-primary pl-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Step 2: Approval</h3>
                    <p class="text-gray-600">Our customer service team will review your return request and provide approval within 24-48 hours.</p>
                </div>
                
                <div class="border-l-4 border-primary pl-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Step 3: Return Authorization</h3>
                    <p class="text-gray-600">Once approved, you will receive a Return Authorization (RA) number and return instructions.</p>
                </div>
                
                <div class="border-l-4 border-primary pl-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Step 4: Ship Product</h3>
                    <p class="text-gray-600">Package the product securely with all original packaging and ship to the address provided in the return instructions.</p>
                </div>
                
                <div class="border-l-4 border-primary pl-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Step 5: Inspection & Refund</h3>
                    <p class="text-gray-600">Upon receiving the product, we will inspect it within 3-5 business days and process your refund if approved.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Return Conditions</h2>
            
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Product Condition</h3>
                    <p class="text-gray-600 mb-2">Products must be returned in their original condition:</p>
                    <ul class="space-y-1 text-gray-600">
                        <li>Original packaging intact</li>
                        <li>All tags and labels attached</li>
                        <li>No signs of use or damage (unless defective)</li>
                        <li>All accessories and components included</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Time Limit</h3>
                    <p class="text-gray-600">Returns must be initiated within 7 days of delivery. Products received after 7 days will not be accepted for return.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Return Authorization</h3>
                    <p class="text-gray-600">All returns require prior approval and a Return Authorization number. Returns without authorization may be rejected.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Proof of Purchase</h3>
                    <p class="text-gray-600">You must provide proof of purchase (order number, invoice, or receipt) when requesting a return.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Refund Policy</h2>
            
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Refund Processing</h3>
                    <p class="text-gray-600 mb-2">Refunds will be processed in the following manner:</p>
                    <ul class="space-y-1 text-gray-600">
                        <li>Refunds processed within 5-7 business days after product inspection</li>
                        <li>Refund amount will be credited to the original payment method</li>
                        <li>For Cash on Delivery orders, refund will be processed via bank transfer</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Refund Amount</h3>
                    <p class="text-gray-600 mb-2">The refund amount will include:</p>
                    <ul class="space-y-1 text-gray-600">
                        <li>Full product price</li>
                        <li>Original shipping charges (if product is defective or wrong item)</li>
                    </ul>
                    <p class="text-gray-600 mt-2">
                        Return shipping charges will be deducted from the refund amount unless the product is defective or wrong item received.
                    </p>
                </div>
                
                <div class="bg-red-50 border-l-4 border-red-400 p-4">
                    <h3 class="font-semibold text-red-800 mb-2">Non-Refundable Items</h3>
                    <ul class="space-y-1 text-red-700">
                        <li>Return shipping charges (unless product is defective)</li>
                        <li>Products damaged due to customer misuse</li>
                        <li>Products returned after 7 days</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Exchange Policy</h2>
            
            <p class="text-gray-600 mb-4">
                We do not offer direct exchanges. If you wish to exchange a product, you must return the original product and place a new order for the desired item.
            </p>
            
            <p class="text-gray-600">
                The return will be processed according to our standard return policy, and you can place a new order for the product you want.
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Special Conditions</h2>
            
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Defective Products</h3>
                    <p class="text-gray-600">Defective products will be replaced or refunded at no additional cost. Return shipping will be free for defective items.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Wrong Product Received</h3>
                    <p class="text-gray-600">If you receive the wrong product, we will arrange free return pickup and provide a full refund or replacement.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Damaged During Shipping</h3>
                    <p class="text-gray-600">Products damaged during shipping will be replaced or refunded. Please report damage within 24 hours of delivery.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Contact Us</h2>
            
            <div class="bg-gray-50 p-6 rounded-lg">
                <p class="text-gray-600 mb-4">
                    For return requests or questions about our return policy, please contact us:
                </p>
                <div class="space-y-2">
                    <p class="text-gray-700">Email: returns@nutrinexus.com</p>
                    <p class="text-gray-700">Phone: 9811388848</p>
                </div>
                <p class="text-gray-600 mt-4">
                    Please have your order number ready when contacting us for faster service.
                </p>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

