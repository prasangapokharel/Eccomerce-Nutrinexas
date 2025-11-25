<?php

namespace App\Traits;

use App\Core\Session;
use App\Helpers\SecurityHelper;

/**
 * Admin CRUD Trait
 * Provides common CRUD operations for admin controllers
 * Reduces code duplication across admin controllers
 */
trait AdminCRUDTrait
{
    /**
     * Validate admin request
     */
    protected function validateAdminRequest(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }

        if (!$this->validateCSRF()) {
            \App\Helpers\NotificationHelper::error('Invalid security token. Please try again.');
            return false;
        }

        return true;
    }

    /**
     * Sanitize input data
     */
    protected function sanitizeInput(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = SecurityHelper::sanitizeString($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Validate required fields
     */
    protected function validateRequired(array $data, array $requiredFields): array
    {
        $errors = [];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        return $errors;
    }

    /**
     * Handle successful operation
     */
    protected function handleSuccess(string $message, string $redirectUrl): void
    {
        \App\Helpers\NotificationHelper::success($message);
        $this->redirect($redirectUrl);
    }

    /**
     * Handle error operation
     */
    protected function handleError(string $message, string $redirectUrl = null): void
    {
        \App\Helpers\NotificationHelper::error($message);
        if ($redirectUrl) {
            $this->redirect($redirectUrl);
        }
    }

    /**
     * Handle validation errors
     */
    protected function handleValidationErrors(array $errors, string $redirectUrl = null): void
    {
        \App\Helpers\NotificationHelper::error(implode('<br>', $errors));
        if ($redirectUrl) {
            $this->redirect($redirectUrl);
        }
    }

    /**
     * Get pagination parameters
     */
    protected function getPaginationParams(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int)($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => $offset
        ];
    }

    /**
     * Format response for AJAX
     */
    protected function jsonSuccess(string $message, array $data = []): void
    {
        $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Format error response for AJAX
     */
    protected function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        $this->jsonResponse([
            'success' => false,
            'message' => $message
        ]);
    }
}

