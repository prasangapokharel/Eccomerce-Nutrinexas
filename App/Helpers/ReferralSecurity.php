<?php
namespace App\Helpers;

use App\Core\Database;
use App\Core\Session;

class ReferralSecurity
{
    private static $db = null;

    /**
     * Get database instance
     */
    private static function getDb()
    {
        if (self::$db === null) {
            try {
                self::$db = Database::getInstance();
            } catch (\Exception $e) {
                error_log('ReferralSecurity: Database connection failed: ' . $e->getMessage());
                return null;
            }
        }
        return self::$db;
    }

    /**
     * Validate referral code and track referral
     */
    public static function validateAndTrackReferral($referralCode, $newUserId)
    {
        if (empty($referralCode) || empty($newUserId)) {
            return false;
        }

        $db = self::getDb();
        if (!$db) {
            return false;
        }

        try {
            // Find referrer by code
            $referrer = $db->query("SELECT id, referral_code FROM users WHERE referral_code = ? AND id != ?", [$referralCode, $newUserId])->single();
            
            if (!$referrer) {
                return false;
            }

            // Check if user is trying to refer themselves
            if ($referrer['id'] == $newUserId) {
                return false;
            }

            // Update new user's referred_by field
            $result = $db->query("UPDATE users SET referred_by = ? WHERE id = ?", [$referrer['id'], $newUserId])->execute();
            
            if ($result) {
                // Log referral for security
                self::logReferralActivity($referrer['id'], $newUserId, 'referral_created');
                return true;
            }

            return false;
        } catch (\Exception $e) {
            error_log('ReferralSecurity: Error validating referral: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate and award commission securely
     */
    public static function awardCommission($orderId, $userId, $orderAmount)
    {
        $db = self::getDb();
        if (!$db) {
            return false;
        }

        try {
            // Get user's referrer
            $user = $db->query("SELECT referred_by FROM users WHERE id = ?", [$userId])->single();
            
            if (!$user || !$user['referred_by']) {
                return false;
            }

            // Get commission rate from settings
            $commissionRate = self::getCommissionRate();
            $commissionAmount = ($orderAmount * $commissionRate) / 100;

            // Insert referral earning
            $earningData = [
                'user_id' => $user['referred_by'],
                'order_id' => $orderId,
                'amount' => $commissionAmount,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];

            $result = $db->query("INSERT INTO referral_earnings (user_id, order_id, amount, status, created_at) VALUES (?, ?, ?, ?, ?)", 
                [$earningData['user_id'], $earningData['order_id'], $earningData['amount'], $earningData['status'], $earningData['created_at']])->execute();

            if ($result) {
                // Update user's referral earnings
                $db->query("UPDATE users SET referral_earnings = referral_earnings + ? WHERE id = ?", [$commissionAmount, $user['referred_by']])->execute();
                
                // Log commission award
                self::logReferralActivity($user['referred_by'], $userId, 'commission_awarded', $commissionAmount);
                
                return true;
            }

            return false;
        } catch (\Exception $e) {
            error_log('ReferralSecurity: Error awarding commission: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get commission rate from settings
     */
    private static function getCommissionRate()
    {
        $db = self::getDb();
        if (!$db) {
            return 5; // Default rate
        }

        try {
            $setting = $db->query("SELECT value FROM settings WHERE `key` = 'commission_rate'")->single();
            return $setting ? (float)$setting['value'] : 5;
        } catch (\Exception $e) {
            return 5;
        }
    }

    /**
     * Log referral activities for security
     */
    private static function logReferralActivity($referrerId, $referredId, $action, $amount = null)
    {
        $db = self::getDb();
        if (!$db) {
            return;
        }

        try {
            $logData = [
                'referrer_id' => $referrerId,
                'referred_id' => $referredId,
                'action' => $action,
                'amount' => $amount,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ];

            $db->query("INSERT INTO referral_logs (referrer_id, referred_id, action, amount, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$logData['referrer_id'], $logData['referred_id'], $logData['action'], $logData['amount'], $logData['ip_address'], $logData['user_agent'], $logData['created_at']])->execute();
        } catch (\Exception $e) {
            error_log('ReferralSecurity: Error logging activity: ' . $e->getMessage());
        }
    }

    /**
     * Validate referral code format
     */
    public static function validateReferralCodeFormat($code)
    {
        // Referral code should be alphanumeric, 6-20 characters
        return preg_match('/^[A-Za-z0-9]{6,20}$/', $code);
    }

    /**
     * Check for suspicious referral activity
     */
    public static function checkSuspiciousActivity($userId, $ipAddress)
    {
        $db = self::getDb();
        if (!$db) {
            return false;
        }

        try {
            // Check for multiple referrals from same IP in short time
            $recentReferrals = $db->query("
                SELECT COUNT(*) as count 
                FROM referral_logs 
                WHERE referrer_id = ? AND ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ", [$userId, $ipAddress])->single();

            return $recentReferrals['count'] > 5; // More than 5 referrals per hour from same IP
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate secure referral code
     */
    public static function generateSecureReferralCode($userId)
    {
        $db = self::getDb();
        if (!$db) {
            return 'NUTRI' . rand(100, 999);
        }

        try {
            $attempts = 0;
            do {
                $code = 'NUTRI' . rand(1000, 9999);
                $existing = $db->query("SELECT id FROM users WHERE referral_code = ?", [$code])->single();
                $attempts++;
            } while ($existing && $attempts < 10);

            if ($attempts >= 10) {
                $code = 'NUTRI' . uniqid();
            }

            // Update user with new code
            $db->query("UPDATE users SET referral_code = ? WHERE id = ?", [$code, $userId])->execute();
            
            return $code;
        } catch (\Exception $e) {
            return 'NUTRI' . rand(100, 999);
        }
    }
}
















