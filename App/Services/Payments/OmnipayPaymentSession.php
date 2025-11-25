<?php

namespace App\Services\Payments;

use Omnipay\Common\Message\ResponseInterface;

class OmnipayPaymentSession
{
    private ResponseInterface $response;
    private int $transactionId;

    public function __construct(ResponseInterface $response, int $transactionId)
    {
        $this->response = $response;
        $this->transactionId = $transactionId;
    }

    public function isSuccessful(): bool
    {
        return $this->response->isSuccessful();
    }

    public function isRedirect(): bool
    {
        return $this->response->isRedirect();
    }

    public function getRedirectUrl(): ?string
    {
        return $this->isRedirect() ? $this->response->getRedirectUrl() : null;
    }

    public function getRedirectMethod(): string
    {
        return $this->response->getRedirectMethod();
    }

    public function getRedirectData(): array
    {
        $data = $this->response->getRedirectData();
        return is_array($data) ? $data : [];
    }

    public function getTransactionReference(): ?string
    {
        return $this->response->getTransactionReference();
    }

    public function getMessage(): ?string
    {
        return $this->response->getMessage();
    }

    public function getTransactionId(): int
    {
        return $this->transactionId;
    }
}



