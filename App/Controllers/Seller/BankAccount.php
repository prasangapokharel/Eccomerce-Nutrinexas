<?php

namespace App\Controllers\Seller;

use Exception;

class BankAccount extends BaseSellerController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \App\Core\Database::getInstance();
    }

    /**
     * Bank account management page
     */
    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUpdate();
            return;
        }

        $bankAccounts = $this->db->query(
            "SELECT * FROM seller_bank_accounts WHERE seller_id = ? ORDER BY is_default DESC, created_at DESC",
            [$this->sellerId]
        )->all();

        $defaultAccount = null;
        foreach ($bankAccounts as $account) {
            if ($account['is_default']) {
                $defaultAccount = $account;
                break;
            }
        }

        $this->view('seller/bank-account/index', [
            'title' => 'Bank Account',
            'bankAccounts' => $bankAccounts,
            'defaultAccount' => $defaultAccount
        ]);
    }

    /**
     * Create or update bank account
     */
    private function handleUpdate()
    {
        try {
            if (!isset($_POST['_csrf_token']) || !\App\Helpers\SecurityHelper::validateCSRF($_POST['_csrf_token'])) {
                $this->setFlash('error', 'Invalid security token. Please try again.');
                $this->redirect('seller/bank-account');
                return;
            }

            $action = $_POST['action'] ?? 'create';
            $accountId = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;

            $data = [
                'seller_id' => $this->sellerId,
                'bank_name' => trim($_POST['bank_name'] ?? ''),
                'account_holder_name' => trim($_POST['account_holder_name'] ?? ''),
                'account_number' => trim($_POST['account_number'] ?? ''),
                'branch_name' => trim($_POST['branch_name'] ?? ''),
                'is_default' => isset($_POST['is_default']) ? 1 : 0
            ];

            // Validate required fields
            if (empty($data['bank_name']) || empty($data['account_holder_name']) || empty($data['account_number'])) {
                $this->setFlash('error', 'Bank name, account holder name, and account number are required');
                $this->redirect('seller/bank-account');
                return;
            }

            // Validate bank name
            if (strlen($data['bank_name']) < 2 || strlen($data['bank_name']) > 100) {
                $this->setFlash('error', 'Bank name must be between 2 and 100 characters');
                $this->redirect('seller/bank-account');
                return;
            }

            if (!preg_match('/^[a-zA-Z0-9\s\-\.&]+$/', $data['bank_name'])) {
                $this->setFlash('error', 'Bank name contains invalid characters');
                $this->redirect('seller/bank-account');
                return;
            }

            // Validate account holder name
            if (strlen($data['account_holder_name']) < 2 || strlen($data['account_holder_name']) > 100) {
                $this->setFlash('error', 'Account holder name must be between 2 and 100 characters');
                $this->redirect('seller/bank-account');
                return;
            }

            if (!preg_match('/^[a-zA-Z\s\.\-]+$/', $data['account_holder_name'])) {
                $this->setFlash('error', 'Account holder name can only contain letters, spaces, dots, and hyphens');
                $this->redirect('seller/bank-account');
                return;
            }

            // Validate account number
            if (!preg_match('/^[0-9]{8,20}$/', $data['account_number'])) {
                $this->setFlash('error', 'Account number must be 8-20 digits');
                $this->redirect('seller/bank-account');
                return;
            }

            // Validate branch name if provided
            if (!empty($data['branch_name'])) {
                if (strlen($data['branch_name']) > 255) {
                    $this->setFlash('error', 'Branch name must not exceed 255 characters');
                    $this->redirect('seller/bank-account');
                    return;
                }
            }

            // Check for duplicate account number for this seller
            $existingAccount = $this->db->query(
                "SELECT id FROM seller_bank_accounts WHERE seller_id = ? AND account_number = ? AND id != ?",
                [$this->sellerId, $data['account_number'], $accountId ?? 0]
            )->single();

            if ($existingAccount) {
                $this->setFlash('error', 'This account number is already registered');
                $this->redirect('seller/bank-account');
                return;
            }

            if ($action === 'update' && $accountId) {
                // Verify account belongs to seller
                $account = $this->db->query(
                    "SELECT id FROM seller_bank_accounts WHERE id = ? AND seller_id = ?",
                    [$accountId, $this->sellerId]
                )->single();

                if (!$account) {
                    $this->setFlash('error', 'Bank account not found or access denied');
                    $this->redirect('seller/bank-account');
                    return;
                }

                // If setting as default, unset other defaults
                if ($data['is_default']) {
                    $this->db->query(
                        "UPDATE seller_bank_accounts SET is_default = 0 WHERE seller_id = ? AND id != ?",
                        [$this->sellerId, $accountId]
                    )->execute();
                }

                $result = $this->db->query(
                    "UPDATE seller_bank_accounts SET 
                     bank_name = ?, account_holder_name = ?, account_number = ?, 
                     branch_name = ?, is_default = ?, updated_at = NOW()
                     WHERE id = ? AND seller_id = ?",
                    [
                        $data['bank_name'], $data['account_holder_name'], $data['account_number'],
                        $data['branch_name'], $data['is_default'],
                        $accountId, $this->sellerId
                    ]
                )->execute();

                if ($result) {
                    $this->setFlash('success', 'Bank account updated successfully');
                } else {
                    $this->setFlash('error', 'Failed to update bank account. Please try again.');
                }
            } else {
                // Create new account
                // If setting as default, unset other defaults
                if ($data['is_default']) {
                    $this->db->query(
                        "UPDATE seller_bank_accounts SET is_default = 0 WHERE seller_id = ?",
                        [$this->sellerId]
                    )->execute();
                }

                $result = $this->db->query(
                    "INSERT INTO seller_bank_accounts 
                     (seller_id, bank_name, account_holder_name, account_number, branch_name, is_default) 
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $data['seller_id'], $data['bank_name'], $data['account_holder_name'],
                        $data['account_number'], $data['branch_name'], $data['is_default']
                    ]
                )->execute();

                if ($result) {
                    $this->setFlash('success', 'Bank account added successfully');
                } else {
                    $this->setFlash('error', 'Failed to add bank account. Please try again.');
                }
            }
        } catch (Exception $e) {
            error_log('Bank account update error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while saving bank account. Please try again.');
        }

        $this->redirect('seller/bank-account');
    }

    /**
     * Delete bank account
     */
    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/bank-account');
            return;
        }

        try {
            $result = $this->db->query(
                "DELETE FROM seller_bank_accounts WHERE id = ? AND seller_id = ?",
                [$id, $this->sellerId]
            )->execute();

            if ($result) {
                $this->setFlash('success', 'Bank account deleted successfully');
            } else {
                $this->setFlash('error', 'Failed to delete bank account');
            }
        } catch (Exception $e) {
            error_log('Delete bank account error: ' . $e->getMessage());
            $this->setFlash('error', 'Error deleting bank account');
        }

        $this->redirect('seller/bank-account');
    }
}

