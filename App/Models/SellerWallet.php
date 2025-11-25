<?php

namespace App\Models;

use App\Core\Model;

class SellerWallet extends Model
{
    protected $table = 'seller_wallet';
    protected $primaryKey = 'id';

    public function getWalletBySellerId($sellerId)
    {
        $wallet = $this->db->query(
            "SELECT * FROM {$this->table} WHERE seller_id = ?",
            [$sellerId]
        )->single();
        
        if (!$wallet) {
            $this->createWallet($sellerId);
            return $this->getWalletBySellerId($sellerId);
        }
        
        return $wallet;
    }

    public function createWallet($sellerId)
    {
        $this->db->query(
            "INSERT INTO {$this->table} (seller_id, balance, total_earnings, total_withdrawals, pending_withdrawals) 
             VALUES (?, 0, 0, 0, 0)",
            [$sellerId]
        )->execute();
        return true;
    }

    public function updateBalance($sellerId, $amount, $type = 'credit')
    {
        $wallet = $this->getWalletBySellerId($sellerId);
        
        if ($type === 'credit') {
            $newBalance = $wallet['balance'] + $amount;
            $totalEarnings = $wallet['total_earnings'] + $amount;
        } else {
            $newBalance = $wallet['balance'] - $amount;
            $totalWithdrawals = $wallet['total_withdrawals'] + $amount;
        }
        
        $data = [
            'balance' => $newBalance
        ];
        
        if ($type === 'credit') {
            $data['total_earnings'] = $totalEarnings;
        } else {
            $data['total_withdrawals'] = $totalWithdrawals;
        }
        
        return $this->update($wallet['id'], $data);
    }

    public function getTransactions($sellerId, $limit = 50, $offset = 0)
    {
        return $this->db->query(
            "SELECT * FROM seller_wallet_transactions 
             WHERE seller_id = ? 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?",
            [$sellerId, $limit, $offset]
        )->all();
    }

    public function addTransaction($data)
    {
        return $this->db->query(
            "INSERT INTO seller_wallet_transactions 
             (seller_id, type, amount, description, order_id, withdraw_request_id, balance_after, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['seller_id'],
                $data['type'],
                $data['amount'],
                $data['description'] ?? null,
                $data['order_id'] ?? null,
                $data['withdraw_request_id'] ?? null,
                $data['balance_after'],
                $data['status'] ?? 'completed'
            ]
        )->execute();
    }
}

