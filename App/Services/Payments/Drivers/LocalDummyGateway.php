<?php

namespace App\Services\Payments\Drivers;

use Omnipay\Common\AbstractGateway;

class LocalDummyGateway extends AbstractGateway
{
    public function getName()
    {
        return 'NutriNexus Local Dummy';
    }

    public function getDefaultParameters()
    {
        return [
            'token' => '',
            'currency' => 'NPR'
        ];
    }

    public function getToken()
    {
        return $this->getParameter('token');
    }

    public function setToken($value)
    {
        return $this->setParameter('token', $value);
    }

    public function getCurrency()
    {
        return $this->getParameter('currency');
    }

    public function setCurrency($value)
    {
        return $this->setParameter('currency', $value);
    }

    public function purchase(array $parameters = [])
    {
        return $this->createRequest(LocalDummyRequest::class, $parameters);
    }

    public function completePurchase(array $parameters = [])
    {
        return $this->createRequest(LocalDummyRequest::class, $parameters);
    }
}




