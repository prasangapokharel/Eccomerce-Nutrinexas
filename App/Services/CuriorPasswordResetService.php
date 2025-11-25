<?php

namespace App\Services;

use App\Helpers\EmailHelper;
use App\Models\Curior\Curior as CuriorModel;

class CuriorPasswordResetService
{
    private $curiorModel;

    public function __construct(?CuriorModel $curiorModel = null)
    {
        $this->curiorModel = $curiorModel ?? new CuriorModel();
    }

    /**
     * Generate a reset token for the given curior id.
     */
    public function generateToken(int $curiorId): ?string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $this->curiorModel->saveResetToken($curiorId, $token, $expiresAt);

        return $token;
    }

    /**
     * Send a password reset email with the given token.
     */
    public function sendResetEmail(array $curior, string $token): bool
    {
        if (empty($curior['email'])) {
            return false;
        }

        $resetUrl = rtrim($this->getBaseUrl(), '/') . '/curior/reset-password/' . $token;
        $firstName = $this->getFirstName($curior['name'] ?? 'Courier');

        return EmailHelper::sendTemplate(
            $curior['email'],
            'Reset Your Curior Password',
            'forgot-password',
            [
                'first_name' => $firstName,
                'reset_url' => $resetUrl,
                'site_name' => $this->getSiteName()
            ],
            $firstName
        );
    }

    /**
     * Send a password changed notification email.
     */
    public function sendPasswordChangedEmail(array $curior): bool
    {
        if (empty($curior['email'])) {
            return false;
        }

        $firstName = $this->getFirstName($curior['name'] ?? 'Courier');

        return EmailHelper::sendTemplate(
            $curior['email'],
            'Your Curior Password Was Updated',
            'password-changed',
            [
                'first_name' => $firstName,
                'site_name' => $this->getSiteName(),
                'change_date' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ],
            $firstName
        );
    }

    private function getFirstName(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return 'Courier';
        }

        $parts = preg_split('/\s+/', $trimmed);
        return $parts[0] ?? 'Courier';
    }

    private function getBaseUrl(): string
    {
        if (defined('BASE_URL')) {
            return BASE_URL;
        }

        if (defined('URLROOT')) {
            return URLROOT;
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . $host;
    }

    private function getSiteName(): string
    {
        return defined('APP_NAME') ? APP_NAME : 'NutriNexus';
    }
}


