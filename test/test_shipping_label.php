<?php
/**
 * Test Shipping Label Generation
 * Tests if all required components are available
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';
require_once ROOT . DS . 'vendor' . DS . 'autoload.php';

echo "=== Shipping Label Test ===\n\n";

// Test 1: Check ZipArchive
echo "1. Checking ZipArchive Extension\n";
if (extension_loaded('zip') && class_exists('ZipArchive')) {
    echo "  ✅ ZipArchive extension is loaded\n";
} else {
    echo "  ❌ ZipArchive extension is NOT loaded\n";
    echo "  Fix: Enable extension=zip in php.ini and restart Apache/XAMPP\n";
    exit(1);
}

// Test 2: Check PHPOffice PhpWord
echo "\n2. Checking PHPOffice PhpWord\n";
if (class_exists('\PhpOffice\PhpWord\TemplateProcessor')) {
    echo "  ✅ PHPOffice PhpWord is installed\n";
} else {
    echo "  ❌ PHPOffice PhpWord is NOT installed\n";
    echo "  Fix: Run: composer require phpoffice/phpword\n";
    exit(1);
}

// Test 3: Check Template File
echo "\n3. Checking Template File\n";
$templatePath = ROOT . '/public/templates/Shipping-Label-Template.docx';
if (file_exists($templatePath)) {
    echo "  ✅ Template file exists: {$templatePath}\n";
    echo "  File size: " . number_format(filesize($templatePath)) . " bytes\n";
} else {
    echo "  ❌ Template file NOT found: {$templatePath}\n";
    exit(1);
}

// Test 4: Try to Load Template
echo "\n4. Testing Template Loading\n";
try {
    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
    echo "  ✅ Template loaded successfully\n";
    
    // Test setting a value
    $templateProcessor->setValue('TEST', 'Test Value');
    echo "  ✅ Can set template values\n";
    
} catch (Exception $e) {
    echo "  ❌ Failed to load template: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== All Tests Passed ===\n";
echo "✅ Shipping label generation should work!\n";
echo "📝 Make sure Apache/XAMPP is restarted if you just enabled zip extension\n";

?>





