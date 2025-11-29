<?php

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../App/Config/database.php';
require_once __DIR__ . '/../App/Core/Database.php';

use App\Core\Database;

$db = Database::getInstance();

try {
    // Get all digital products without download links or with empty links
    $products = $db->query("
        SELECT id, product_id 
        FROM digital_product 
        WHERE file_download_link IS NULL 
           OR file_download_link = '' 
           OR TRIM(file_download_link) = ''
    ")->all();

    if (empty($products)) {
        echo "✅ All digital products already have download links!\n";
        exit(0);
    }

    $updated = 0;
    foreach ($products as $product) {
        $dummyLink = "https://example.com/downloads/product-{$product['product_id']}.pdf";
        
        $db->query("
            UPDATE digital_product 
            SET file_download_link = ? 
            WHERE id = ?
        ", [$dummyLink, $product['id']])->execute();
        
        $updated++;
    }

    echo "✅ Updated {$updated} digital product(s) with dummy download links!\n";
} catch (Exception $e) {
    echo "❌ Error adding dummy download links: " . $e->getMessage() . "\n";
    exit(1);
}

