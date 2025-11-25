<?php ob_start(); ?>

<div class="min-h-screen bg-neutral-50">
    <div class="container mx-auto px-4 py-6 max-w-4xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary mb-2">NX Guide</h1>
            <p class="text-neutral-600">Learn how to use NutriNexus platform</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-neutral-200 mb-6 overflow-hidden">
            <div class="p-6 border-b border-neutral-200">
                <h2 class="text-2xl font-semibold text-primary">How to Register?</h2>
                <p class="text-neutral-600 mt-2">Step-by-step guide to create your account</p>
            </div>
            <div class="p-6">
                <div class="aspect-video bg-neutral-100 rounded-lg overflow-hidden mb-4">
                    <iframe 
                        class="w-full h-full" 
                        src="https://www.youtube.com/embed/dQw4w9WgXcQ" 
                        title="How to Register on NutriNexus"
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="space-y-2 text-neutral-700">
                    <p class="font-medium">Steps:</p>
                    <ol class="list-decimal list-inside space-y-1 ml-2">
                        <li>Click on "Sign In" button</li>
                        <li>Select "Create Account" or "Register"</li>
                        <li>Fill in your details (name, email, phone)</li>
                        <li>Create a secure password</li>
                        <li>Verify your email/phone number</li>
                        <li>Complete your profile</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-neutral-200 mb-6 overflow-hidden">
            <div class="p-6 border-b border-neutral-200">
                <h2 class="text-2xl font-semibold text-primary">How to Register as Seller?</h2>
                <p class="text-neutral-600 mt-2">Become a seller and start selling on NutriNexus</p>
            </div>
            <div class="p-6">
                <div class="aspect-video bg-neutral-100 rounded-lg overflow-hidden mb-4">
                    <iframe 
                        class="w-full h-full" 
                        src="https://www.youtube.com/embed/dQw4w9WgXcQ" 
                        title="How to Register as Seller on NutriNexus"
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="space-y-2 text-neutral-700">
                    <p class="font-medium">Steps:</p>
                    <ol class="list-decimal list-inside space-y-1 ml-2">
                        <li>Go to Seller Registration page</li>
                        <li>Fill in business information</li>
                        <li>Upload required documents</li>
                        <li>Submit for approval</li>
                        <li>Wait for admin approval</li>
                        <li>Start adding products after approval</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-neutral-200 mb-6 overflow-hidden">
            <div class="p-6 border-b border-neutral-200">
                <h2 class="text-2xl font-semibold text-primary">How to Place External Banner Ads?</h2>
                <p class="text-neutral-600 mt-2">Secure Tier 1, Tier 2, or Tier 3 placements in just three steps.</p>
            </div>
            <div class="p-6 space-y-4">
                <ol class="list-decimal list-inside space-y-2 text-neutral-700">
                    <li>Review the <a href="<?= \App\Core\View::url('ads') ?>" class="text-primary font-semibold hover:underline">ad pricing table</a> to choose the tier that fits your KPI.</li>
                    <li>Share creatives (JPG/PNG, 1200×500 or 1080×1080) and preferred flight dates.</li>
                    <li>Approve the preview link we send and submit payment to go live.</li>
                </ol>
                <div class="bg-neutral-50 border border-dashed border-neutral-200 rounded-xl p-4 text-sm text-neutral-600">
                    <p class="font-semibold text-mountain mb-1">Specs quick recap</p>
                    <p>Max file size 500 KB · Include CTA copy · Provide destination URL or WhatsApp number for direct leads.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="https://wa.me/9811388848?text=I%20want%20to%20place%20an%20external%20banner%20ad%20on%20NutriNexus"
                       target="_blank"
                       rel="noopener"
                       class="btn btn-primary">
                        Get Quote on WhatsApp
                    </a>
                    <a href="mailto:ads@nutrinexus.com?subject=External%20Banner%20Ad%20Quote"
                       class="btn btn-secondary">
                        Email Media Desk
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
            <div class="p-6 border-b border-neutral-200">
                <h2 class="text-2xl font-semibold text-primary">Need More Help?</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <a href="<?= \App\Core\View::url('contact') ?>" class="block p-4 border border-neutral-200 rounded-lg text-primary hover:bg-primary/5">
                        <h3 class="font-semibold text-mountain mb-1">Contact Support</h3>
                        <p class="text-sm text-neutral-600">Get help from our support team</p>
                    </a>
                    <a href="<?= \App\Core\View::url('pages/faq') ?>" class="block p-4 border border-neutral-200 rounded-lg text-primary hover:bg-primary/5">
                        <h3 class="font-semibold text-mountain mb-1">FAQ</h3>
                        <p class="text-sm text-neutral-600">Frequently asked questions</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

