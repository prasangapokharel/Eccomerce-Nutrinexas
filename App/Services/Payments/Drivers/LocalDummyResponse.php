<?php

namespace App\Services\Payments\Drivers;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

class LocalDummyResponse extends AbstractResponse
{
    public function __construct(RequestInterface $request, $data)
    {
        parent::__construct($request, $data);
    }

    public function isSuccessful()
    {
        return (bool) ($this->data['success'] ?? false);
    }

    public function isRedirect()
    {
        return false;
    }

    public function getTransactionReference()
    {
        return $this->data['transactionId'] ?? null;
    }

    public function getMessage()
    {
        return $this->data['message'] ?? null;
    }
}









