<?php ob_start(); ?>
<?php $page = 'products'; ?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Bulk Upload Products</h1>
            <p class="mt-1 text-sm text-gray-500">Upload multiple products using CSV file</p>
        </div>
        <a href="<?= \App\Core\View::url('seller/products') ?>" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Products
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Upload CSV File</h2>
            
            <form action="<?= \App\Core\View::url('seller/products/bulk-upload') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                
                <div class="space-y-4">
                    <div>
                        <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-2">
                            CSV File <span class="text-red-500">*</span>
                        </label>
                        <input type="file" 
                               id="csv_file" 
                               name="csv_file" 
                               accept=".csv"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        <p class="text-xs text-gray-500 mt-1">Upload a CSV file with product data</p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-blue-900 mb-2">CSV Format Requirements</h3>
                        <div class="text-sm text-blue-800 space-y-1">
                            <p><strong>Required columns:</strong> product_name, price, category, stock_quantity</p>
                            <p><strong>Optional columns:</strong> description, short_description, sale_price, subcategory, product_type_main, product_type, colors, weight, serving, flavor, image_url, is_digital (yes/no)</p>
                            <p class="mt-2"><strong>Note:</strong> All products will be created with "pending" status and require admin approval.</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Sample CSV Format</h3>
                        <pre class="text-xs text-gray-700 overflow-x-auto">product_name,price,category,stock_quantity,description,image_url
"Energy Bar",250.00,"Energy Bars",100,"High energy protein bar","https://example.com/image.jpg"
"Protein Powder",1500.00,"Supplements",50,"Whey protein powder","https://example.com/protein.jpg"</pre>
                        <a href="<?= \App\Core\View::url('seller/products/download-csv-template') ?>" 
                           class="inline-flex items-center mt-2 text-sm text-primary hover:underline">
                            <i class="fas fa-download mr-1"></i>
                            Download CSV Template
                        </a>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="<?= \App\Core\View::url('seller/products') ?>" 
                       class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                        <i class="fas fa-upload mr-2"></i>
                        Upload CSV
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

