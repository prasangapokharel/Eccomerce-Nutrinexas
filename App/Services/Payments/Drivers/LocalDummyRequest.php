<?php

namespace App\Services\Payments\Drivers;

use Omnipay\Common\Message\AbstractRequest;

class LocalDummyRequest extends AbstractRequest
{
    public function getData()
    {
        $this->validate('amount', 'transactionId');
        return [
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'transactionId' => $this->getTransactionId(),
            'returnUrl' => $this->getReturnUrl(),
            'cancelUrl' => $this->getCancelUrl()
        ];
    }

    public function sendData($data)
    {
        return new LocalDummyResponse($this, [
            'success' => true,
            'transactionId' => $data['transactionId'],
            'message' => 'Simulated payment approved',
            'redirect' => false
        ]);
    }
}









