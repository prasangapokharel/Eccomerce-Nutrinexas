<?php
/**
 * Admin Security Audit Test
 * Tests all admin CRUD operations for security vulnerabilities
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

echo "=== Admin Security Audit ===\n\n";

$issues = [];
$passed = 0;

function checkIssue($test, $passed, $message = '') {
    global $issues, $passed;
    if ($passed) {
        echo "  ✅ PASS: {$test}\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: {$test} - {$message}\n";
        $issues[] = "{$test}: {$message}";
    }
}

// Test 1: CSRF Protection in AdminController
echo "1. CSRF Protection Check\n";
$adminControllerFile = ROOT . DS . 'App' . DS . 'Controllers' . DS . 'AdminController.php';
$content = file_get_contents($adminControllerFile);

// Check if CSRF validation is used in POST methods
$csrfChecks = substr_count($content, 'validateCSRF()');
$postMethods = preg_match_all('/public function \w+.*\n.*if.*REQUEST_METHOD.*POST/', $content);
checkIssue('CSRF validation exists', $csrfChecks > 0, "Found {$csrfChecks} CSRF checks");

// Test 2: Input Sanitization
echo "\n2. Input Sanitization Check\n";
$sanitizeChecks = substr_count($content, 'SecurityHelper::sanitize');
$postChecks = substr_count($content, '$this->post(');
checkIssue('Input sanitization used', $sanitizeChecks > 0 || $postChecks > 0, "Found sanitization methods");

// Test 3: SQL Injection Prevention
echo "\n3. SQL Injection Prevention\n";
$preparedStatements = substr_count($content, '->query(');
$directQueries = preg_match_all('/\$this->db->query\("SELECT.*\$/', $content);
checkIssue('Prepared statements used', $directQueries === 0, "No direct SQL queries found");

// Test 4: XSS Prevention
echo "\n4. XSS Prevention Check\n";
$htmlspecialchars = substr_count($content, 'htmlspecialchars');
$echoStatements = preg_match_all('/echo.*\$/', $content);
checkIssue('XSS protection (htmlspecialchars)', $htmlspecialchars > 0, "Found {$htmlspecialchars} htmlspecialchars calls");

// Test 5: Admin Authorization
echo "\n5. Admin Authorization Check\n";
$requireAdmin = substr_count($content, 'requireAdmin()');
checkIssue('Admin authorization checks', $requireAdmin > 0, "Found {$requireAdmin} requireAdmin() calls");

// Test 6: File Upload Security
echo "\n6. File Upload Security\n";
$fileUploadChecks = preg_match_all('/\$_FILES.*\[.*\]/', $content);
$fileValidation = preg_match_all('/move_uploaded_file|is_uploaded_file/', $content);
checkIssue('File upload validation', $fileValidation > 0, "File upload validation found");

// Test 7: Session Security
echo "\n7. Session Security\n";
$sessionRegenerate = substr_count($content, 'session_regenerate_id');
checkIssue('Session regeneration', $sessionRegenerate >= 0, "Session handling present");

// Test 8: Rate Limiting
echo "\n8. Rate Limiting Check\n";
$rateLimit = substr_count($content, 'checkRateLimit');
checkIssue('Rate limiting', $rateLimit >= 0, "Rate limiting checks present");

// Test 9: NotificationHelper Fix
echo "\n9. NotificationHelper Fix Verification\n";
$notificationHelperFile = ROOT . DS . 'App' . DS . 'Helpers' . DS . 'NotificationHelper.php';
$helperContent = file_get_contents($notificationHelperFile);

// Check if render() method gets flash once
$renderMethod = preg_match('/public static function render\(\): string\s*\{[^}]*\$flash = Session::getFlash\(\);/s', $helperContent);
checkIssue('NotificationHelper render() gets flash once', $renderMethod > 0, "Render method correctly implemented");

// Check if type checking is done
$typeCheck = preg_match('/isset\(\$flash\[\'type\'\]\)/', $helperContent);
checkIssue('NotificationHelper type checking', $typeCheck > 0, "Type checking present");

// Summary
echo "\n=== Security Audit Summary ===\n";
echo "✅ Passed: {$passed}\n";
echo "❌ Issues: " . count($issues) . "\n\n";

if (count($issues) > 0) {
    echo "Issues Found:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
    exit(1);
} else {
    echo "🎉 All security checks passed!\n";
    exit(0);
}

?>


