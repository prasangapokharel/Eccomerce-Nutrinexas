<?php
/**
 * Quick check if ZipArchive is available in web context
 * Access: http://192.168.1.125:8000/check_zip.php
 */

header('Content-Type: text/plain');

echo "=== Web Server PHP Extension Check ===\n\n";

echo "1. ZipArchive Extension:\n";
if (extension_loaded('zip')) {
    echo "   ✅ extension_loaded('zip') = TRUE\n";
} else {
    echo "   ❌ extension_loaded('zip') = FALSE\n";
}

if (class_exists('ZipArchive')) {
    echo "   ✅ class_exists('ZipArchive') = TRUE\n";
} else {
    echo "   ❌ class_exists('ZipArchive') = FALSE\n";
    echo "   ⚠️  RESTART APACHE/XAMPP to enable ZipArchive!\n";
}

echo "\n2. PHPOffice PhpWord:\n";
require_once dirname(__DIR__) . '/vendor/autoload.php';
if (class_exists('\PhpOffice\PhpWord\TemplateProcessor')) {
    echo "   ✅ PHPOffice PhpWord is installed\n";
} else {
    echo "   ❌ PHPOffice PhpWord is NOT installed\n";
}

echo "\n3. Template File:\n";
$templatePath = dirname(__DIR__) . '/public/templates/Shipping-Label-Template.docx';
if (file_exists($templatePath)) {
    echo "   ✅ Template exists: " . basename($templatePath) . "\n";
} else {
    echo "   ❌ Template NOT found\n";
}

echo "\n=== Summary ===\n";
if (extension_loaded('zip') && class_exists('ZipArchive') && class_exists('\PhpOffice\PhpWord\TemplateProcessor')) {
    echo "✅ All requirements met! Shipping labels should work.\n";
} else {
    echo "❌ Some requirements missing. Check above.\n";
    if (!extension_loaded('zip') || !class_exists('ZipArchive')) {
        echo "\n⚠️  ACTION REQUIRED: Restart Apache/XAMPP to enable ZipArchive extension!\n";
    }
}

?>





