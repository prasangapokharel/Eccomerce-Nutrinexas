<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\Setting;
use App\Models\User;
use App\Models\Withdrawal;

class AdminSettingController extends Controller
{
    private $settingModel;
    private $userModel;
    private $withdrawalModel;

    public function __construct()
    {   
        parent::__construct();
        $this->settingModel = new Setting();
        $this->userModel = new User();
        $this->withdrawalModel = new Withdrawal();
        
        // Check if user is admin
        $this->requireAdmin();
    }

    /**
     * Display the settings page
     */
    public function index()
    {
        // Get all settings
        $settings = $this->settingModel->getAll();
        
        // Render the settings page
        $this->view('admin/settings/index', [
            'settings' => $settings,
            'title' => 'Admin Settings',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => 'admin'],
                ['title' => 'Settings', 'url' => 'admin/settings', 'active' => true]
            ]
        ]);
    }

    /**
     * Update settings
     */
    public function update()
    {
        // Check if request is POST
        if (Request::method() !== 'POST') {
            Response::json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        // Get request data
        $data = Request::json();
        
        // Validate data
        if (!$this->validateSettings($data)) {
            Response::json(['success' => false, 'message' => 'Invalid settings data']);
            return;
        }

        // Update settings
        try {
            // Begin transaction using the model's transaction methods
            $this->settingModel->beginTransaction();
            
            // Update minimum withdrawal amount
            $this->settingModel->set('min_withdrawal', $data['min_withdrawal']);
            
            // Update auto approve withdrawals
            $this->settingModel->set('auto_approve', $data['auto_approve'] ? 'true' : 'false');
            
            // Update commission rate
            $this->settingModel->set('commission_rate', $data['commission_rate']);

            // Update tax rate
            if (isset($data['tax_rate'])) {
                $this->settingModel->set('tax_rate', $data['tax_rate']);
            }

            // Update remember token days
            $this->settingModel->set('remember_token_days', $data['remember_token_days']);

            // Update enable remember me
            $this->settingModel->set('enable_remember_me', $data['enable_remember_me'] ? 'true' : 'false');

            // Update website URL (Base URL used across site and admin)
            if (!empty($data['website_url'])) {
                $this->settingModel->set('website_url', rtrim($data['website_url'], '/'));
            }
            
            // Commit transaction
            $this->settingModel->commit();
            
            Response::json(['success' => true, 'message' => 'Settings updated successfully']);
        } catch (\Exception $e) {
            // Rollback transaction on error
            if ($this->settingModel->inTransaction()) {
                $this->settingModel->rollBack();
            }
            Response::json(['success' => false, 'message' => 'Error updating settings: ' . $e->getMessage()]);
        }
    }

    /**
     * Optimize database
     */
    public function optimizeDb()
    {
        // Check if request is POST
        if (Request::method() !== 'POST') {
            Response::json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        try {
            // Begin transaction
            $this->settingModel->beginTransaction();
            
            // Get database connection
            $db = $this->settingModel->getDb();
            
            // Get all tables using Database helper
            $rows = $db->query("SHOW TABLES")->all();
            $tables = array_map(static function ($row) {
                // Each row from SHOW TABLES has a single column
                return current($row);
            }, $rows);
            
            // Optimize each table
            foreach ($tables as $table) {
                $db->query("OPTIMIZE TABLE `{$table}`")->execute();
            }
            
            // Commit transaction
            $this->settingModel->commit();
            
            Response::json(['success' => true, 'message' => 'Database optimized successfully']);
        } catch (\Exception $e) {
            // Rollback transaction on error
            if ($this->settingModel->inTransaction()) {
                $this->settingModel->rollBack();
            }
            Response::json(['success' => false, 'message' => 'Error optimizing database: ' . $e->getMessage()]);
        }
    }

    /**
     * Backup database
     */
    public function backupDb()
    {
        // Check if request is POST
        if (Request::method() !== 'POST') {
            Response::json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        try {
            // Begin transaction
            $this->settingModel->beginTransaction();
            
            // Get database connection
            $db = $this->settingModel->getDb();
            
            // Get database config
            $config = require_once __DIR__ . '/../../config/database.php';
            $dbName = $config['database'];
            
            // Create backup directory if it doesn't exist
            $backupDir = __DIR__ . '/../../storage/backups';
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // Generate backup filename
            $backupFile = $backupDir . '/' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Create backup command
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['host']),
                escapeshellarg($dbName),
                escapeshellarg($backupFile)
            );
            
            // Execute backup command
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                throw new \Exception('Error executing backup command');
            }
            
            // Generate download URL
            $downloadUrl = BASE_URL . '/admin/settings/download-backup?file=' . basename($backupFile);
            
            // Commit transaction
            $this->settingModel->commit();
            
            Response::json([
                'success' => true, 
                'message' => 'Database backup created successfully',
                'download_url' => $downloadUrl
            ]);
        } catch (\Exception $e) {
            // Rollback transaction on error
            if ($this->settingModel->inTransaction()) {
                $this->settingModel->rollBack();
            }
            Response::json(['success' => false, 'message' => 'Error backing up database: ' . $e->getMessage()]);
        }
    }

    /**
     * Download database backup
     */
    public function downloadBackup()
    {
        // Get backup filename
        $filename = Request::get('file');
        
        // Validate filename
        if (empty($filename) || !preg_match('/^[a-zA-Z0-9_]+_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename)) {
            Response::json(['success' => false, 'message' => 'Invalid backup filename']);
            return;
        }
        
        // Get backup file path
        $backupFile = __DIR__ . '/../../storage/backups/' . $filename;
        
        // Check if file exists
        if (!file_exists($backupFile)) {
            Response::json(['success' => false, 'message' => 'Backup file not found']);
            return;
        }
        
        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($backupFile));
        
        // Clear output buffer
        ob_clean();
        flush();
        
        // Read file and output to browser
        readfile($backupFile);
        exit;
    }

    /**
     * Validate settings data
     */
    private function validateSettings($data)
    {
        // Check if required fields exist
        if (!isset($data['min_withdrawal']) || 
            !isset($data['auto_approve']) || 
            !isset($data['commission_rate']) || 
            !isset($data['tax_rate']) || 
            !isset($data['remember_token_days']) || 
            !isset($data['enable_remember_me']) ||
            !isset($data['website_url'])) {
            return false;
        }
        
        // Validate minimum withdrawal amount
        if (!is_numeric($data['min_withdrawal']) || $data['min_withdrawal'] < 0) {
            return false;
        }
        
        // Validate commission rate
        if (!is_numeric($data['commission_rate']) || $data['commission_rate'] < 0 || $data['commission_rate'] > 100) {
            return false;
        }

        // Validate tax rate
        if (!is_numeric($data['tax_rate']) || $data['tax_rate'] < 0 || $data['tax_rate'] > 100) {
            return false;
        }
        
        // Validate remember token days
        if (!is_numeric($data['remember_token_days']) || $data['remember_token_days'] < 1 || $data['remember_token_days'] > 365) {
            return false;
        }

        // Validate website URL
        $url = trim((string)$data['website_url']);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        // Only allow http/https schemes
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array(strtolower($scheme), ['http', 'https'])) {
            return false;
        }

        return true;
    }

    /**
     * Export database as an Excel-compatible HTML file
     */
    public function exportDbXls()
    {
        // Ensure only admins can access
        $this->requireAdmin();

        try {
            $db = Database::getInstance();
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            return;
        }

        // Gather all table names
        $tables = [];
        try {
            $result = $db->query('SHOW TABLES')->all();
            foreach ($result as $row) {
                $tables[] = array_values($row)[0];
            }
        } catch (\Exception $e) {
            $tables = [];
        }

        // Prepare Excel-compatible HTML
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        foreach ($tables as $table) {
            $html .= '<h2 style="font-family: Arial;">' . htmlspecialchars($table) . '</h2>';
            try {
                $rows = $db->query('SELECT * FROM `' . $table . '`')->all();
                if (empty($rows)) {
                    $html .= '<p>No data</p>';
                    continue;
                }
                // Headers
                $columns = array_keys($rows[0]);
                $html .= '<table border="1" cellspacing="0" cellpadding="4" style="border-collapse: collapse; font-family: Arial;">';
                $html .= '<thead><tr>';
                foreach ($columns as $col) {
                    $html .= '<th style="background:#f3f4f6">' . htmlspecialchars($col) . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                // Rows
                foreach ($rows as $r) {
                    $html .= '<tr>';
                    foreach ($columns as $col) {
                        $val = isset($r[$col]) ? (string)$r[$col] : '';
                        $html .= '<td>' . htmlspecialchars($val) . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
            } catch (\Exception $e) {
                $html .= '<p>Error reading table: ' . htmlspecialchars($table) . '</p>';
            }
        }
        $html .= '</body></html>';

        $filename = 'database_export_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $html;
    }
}