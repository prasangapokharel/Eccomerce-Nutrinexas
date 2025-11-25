<?php

namespace App\Commands;

use App\Core\Command;
use App\Core\Database;

class CustomerTable extends Command
{
    protected $signature = 'create:customer-table';
    protected $description = 'Create customer table with required fields';

    public function execute(array $args = []): int
    {
        try {
            $this->output('Starting customer table creation...', 'info');
            
            $db = new Database();
            
            // Create customers table
            $sql = "CREATE TABLE IF NOT EXISTS customers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_name VARCHAR(255) NOT NULL,
                contact_no VARCHAR(20) NOT NULL,
                address TEXT NOT NULL,
                email VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_contact_no (contact_no),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $result = $db->query($sql)->execute();
            
            if ($result) {
                $this->output('Customer table created successfully!', 'success');
                
                // Insert sample data from composer.json
                $this->insertSampleData($db);
                
            } else {
                $this->output('Failed to create customer table', 'error');
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->output('Error creating customer table: ' . $e->getMessage(), 'error');
            return 1;
        }
        
        return 0;
    }
    
    private function insertSampleData(Database $db)
    {
        try {
            // Sample customer data
            $customers = [
                [
                    'customer_name' => 'John Doe',
                    'contact_no' => '+977-9812345678',
                    'address' => '123 Main Street, Kathmandu, Nepal',
                    'email' => 'john.doe@example.com'
                ],
                [
                    'customer_name' => 'Jane Smith',
                    'contact_no' => '+977-9876543210',
                    'address' => '456 Oak Avenue, Lalitpur, Nepal',
                    'email' => 'jane.smith@example.com'
                ],
                [
                    'customer_name' => 'Mike Johnson',
                    'contact_no' => '+977-9854321098',
                    'address' => '789 Pine Road, Bhaktapur, Nepal',
                    'email' => 'mike.johnson@example.com'
                ]
            ];
            
            $inserted = 0;
            foreach ($customers as $customer) {
                $sql = "INSERT INTO customers (customer_name, contact_no, address, email) VALUES (?, ?, ?, ?)";
                $result = $db->query($sql)->bind([
                    $customer['customer_name'],
                    $customer['contact_no'],
                    $customer['address'],
                    $customer['email']
                ])->execute();
                
                if ($result) {
                    $inserted++;
                }
            }
            
            if ($inserted > 0) {
                $this->output("Inserted {$inserted} sample customers", 'success');
            }
            
        } catch (\Exception $e) {
            $this->output('Warning: Could not insert sample data: ' . $e->getMessage(), 'warning');
        }
    }
}
