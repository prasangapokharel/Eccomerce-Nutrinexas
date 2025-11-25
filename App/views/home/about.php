<?php ob_start(); ?>
<div class="container mx-auto px-4 py-12">
    <div class="max-w-5xl mx-auto">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center">About Nutri Nexus</h1>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-12">
            <div class="md:flex">
                <div class="md:w-1/2">
                    <img src="<?= \App\Core\View::asset('images/about-us.jpg') ?>" alt="About Nutri Nexus" class="w-full h-full object-cover">
                </div>
                <div class="md:w-1/2 p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Our Story</h2>
                    <p class="text-gray-600 mb-4">
                        Founded in 2018, Nutri Nexus was born out of a passion for fitness and a frustration with the lack of high-quality supplements in the market. Our founder, a fitness enthusiast and nutrition expert, set out to create a brand that would provide premium quality supplements without compromise.
                    </p>
                    <p class="text-gray-600">
                        What started as a small operation has now grown into one of India's most trusted supplement brands, serving thousands of fitness enthusiasts across the country. Our commitment to quality, transparency, and customer satisfaction remains at the core of everything we do.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Our Mission</h2>
            <div class="bg-primary-lightest p-8 rounded-lg text-center">
                <p class="text-lg text-gray-700 italic">
                    "To empower individuals on their fitness journey by providing premium quality supplements that are effective, safe, and transparent in their formulation."
                </p>
            </div>
        </div>
        
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">What Sets Us Apart</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Premium Quality</h3>
                    <p class="text-gray-600">
                        We source the highest quality ingredients and maintain strict quality control throughout our manufacturing process.
                    </p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-flask text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Science-Backed</h3>
                    <p class="text-gray-600">
                        All our formulations are based on scientific research and designed to deliver real results.
                    </p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Transparency</h3>
                    <p class="text-gray-600">
                        We believe in complete transparency about what goes into our products, with no proprietary blends or hidden ingredients.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Our Team</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <img src="<?= \App\Core\View::asset('images/team/founder.jpg') ?>" alt="Founder" class="w-full h-48 object-cover">
                    <div class="p-4 text-center">
                        <h3 class="text-lg font-semibold text-gray-900">Rajesh Kumar</h3>
                        <p class="text-sm text-gray-600">Founder & CEO</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <img src="<?= \App\Core\View::asset('images/team/nutritionist.jpg') ?>" alt="Nutritionist" class="w-full h-48 object-cover">
                    <div class="p-4 text-center">
                        <h3 class="text-lg font-semibold text-gray-900">Dr. Priya Sharma</h3>
                        <p class="text-sm text-gray-600">Chief Nutritionist</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <img src="<?= \App\Core\View::asset('images/team/product-manager.jpg') ?>" alt="Product Manager" class="w-full h-48 object-cover">
                    <div class="p-4 text-center">
                        <h3 class="text-lg font-semibold text-gray-900">Vikram Singh</h3>
                        <p class="text-sm text-gray-600">Product Manager</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <img src="<?= \App\Core\View::asset('images/team/customer-support.jpg') ?>" alt="Customer Support" class="w-full h-48 object-cover">
                    <div class="p-4 text-center">
                        <h3 class="text-lg font-semibold text-gray-900">Anita Patel</h3>
                        <p class="text-sm text-gray-600">Customer Support Lead</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Our Certifications</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-lg shadow-md p-4 flex items-center justify-center">
                    <img src="<?= \App\Core\View::asset('images/certifications/fssai.png') ?>" alt="FSSAI Certified" class="h-16">
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-4 flex items-center justify-center">
                    <img src="<?= \App\Core\View::asset('images/certifications/gmp.png') ?>" alt="GMP Certified" class="h-16">
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-4 flex items-center justify-center">
                    <img src="<?= \App\Core\View::asset('images/certifications/iso.png') ?>" alt="ISO Certified" class="h-16">
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-4 flex items-center justify-center">
                    <img src="<?= \App\Core\View::asset('images/certifications/haccp.png') ?>" alt="HACCP Certified" class="h-16">
                </div>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>

<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
