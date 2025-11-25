<?php

namespace App\Services\Payments;

use App\Models\GatewayTransaction;
use App\Models\PaymentGateway;
use InvalidArgumentException;
use Omnipay\Common\GatewayInterface;
use Omnipay\Common\Message\ResponseInterface;
use Omnipay\Omnipay;

class OmnipayGatewayManager
{
    private PaymentGateway $gatewayModel;
    private GatewayTransaction $transactionModel;
    private array $config;

    public function __construct(
        ?PaymentGateway $gatewayModel = null,
        ?GatewayTransaction $transactionModel = null,
        ?array $config = null
    ) {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 3);
        $this->gatewayModel = $gatewayModel ?? new PaymentGateway();
        $this->transactionModel = $transactionModel ?? new GatewayTransaction();
        $this->config = $config ?? require $root . '/config/omnipay.php';
    }

    public function getDefaultCurrency(): string
    {
        return $this->config['default_currency'] ?? 'USD';
    }

    public function getAvailableDrivers(): array
    {
        return array_keys($this->config['drivers'] ?? []);
    }

    public function createGateway(string $slug): GatewayInterface
    {
        $gatewayRecord = $this->gatewayModel->getBySlug($slug);
        if (!$gatewayRecord) {
            throw new InvalidArgumentException("Gateway {$slug} not found.");
        }

        return $this->createGatewayFromRecord($gatewayRecord);
    }

    public function createGatewayFromRecord(array $gatewayRecord): GatewayInterface
    {
        $parameters = $this->decodeParameters($gatewayRecord);
        $driver = $parameters['driver'] ?? $gatewayRecord['driver'] ?? $this->config['default_driver'] ?? 'Dummy';

        if (!$this->isDriverAllowed($driver)) {
            throw new InvalidArgumentException("Driver {$driver} is not whitelisted.");
        }

        $gateway = Omnipay::create($driver);
        $this->applySettings($gateway, $parameters, $gatewayRecord);
        return $gateway;
    }

    public function initiatePurchase(array $gatewayRecord, array $payload): OmnipayPaymentSession
    {
        $gateway = $this->createGatewayFromRecord($gatewayRecord);

        $requestPayload = $this->formatPayload($payload);
        $response = $gateway->purchase($requestPayload)->send();

        $transactionId = $this->transactionModel->createPending([
            'order_id' => $payload['order_id'],
            'gateway_slug' => $gatewayRecord['slug'] ?? ($gatewayRecord['name'] ?? 'custom'),
            'driver' => $this->resolveDriver($gatewayRecord),
            'provider_reference' => $response->getTransactionReference(),
            'request_payload' => $requestPayload,
            'response_payload' => $this->extractResponsePayload($response),
            'metadata' => $payload['metadata'] ?? []
        ]);

        return new OmnipayPaymentSession($response, $transactionId);
    }

    public function completePurchase(array $gatewayRecord, array $payload): ResponseInterface
    {
        $gateway = $this->createGatewayFromRecord($gatewayRecord);
        $response = $gateway->completePurchase($payload)->send();

        $reference = $response->getTransactionReference();
        if ($reference) {
            $transaction = $this->transactionModel->findByReference($reference);
            if ($transaction) {
                $status = $response->isSuccessful() ? 'completed' : 'failed';
                $this->transactionModel->updateStatus($transaction['id'], $status, [
                    'provider_reference' => $reference,
                    'response_payload' => $this->extractResponsePayload($response)
                ]);
            }
        }

        return $response;
    }

    public function decodeParameters(array $gatewayRecord): array
    {
        $raw = $gatewayRecord['parameters'] ?? '{}';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function resolveDriver(array $gatewayRecord): string
    {
        $parameters = $this->decodeParameters($gatewayRecord);
        return $parameters['driver'] ?? $gatewayRecord['driver'] ?? $this->config['default_driver'] ?? 'Dummy';
    }

    private function applySettings(GatewayInterface $gateway, array $parameters, array $record): void
    {
        $testMode = $record['is_test_mode'] ?? false;
        if (isset($parameters['test_mode'])) {
            $testMode = (bool) $parameters['test_mode'];
        }

        if (method_exists($gateway, 'setTestMode')) {
            $gateway->setTestMode((bool) $testMode);
        }

        if (method_exists($gateway, 'setCurrency')) {
            $currency = $parameters['currency'] ?? $this->getDefaultCurrency();
            $gateway->setCurrency($currency);
        }

        foreach ($parameters as $key => $value) {
            if ($value === '' || $value === null || $key === 'driver' || $key === 'currency') {
                continue;
            }
            $method = $this->buildSetterName($key);
            if (method_exists($gateway, $method)) {
                $gateway->{$method}($value);
            }
        }
    }

    private function buildSetterName(string $key): string
    {
        $key = str_replace(['-', '_'], ' ', $key);
        $key = str_replace(' ', '', ucwords($key));
        return 'set' . $key;
    }

    private function formatPayload(array $payload): array
    {
        $amount = isset($payload['amount']) ? (float) $payload['amount'] : 0;

        $defaults = [
            'currency' => $this->getDefaultCurrency(),
            'description' => 'NutriNexus Order Payment',
            'transactionId' => $payload['transactionId'] ?? $payload['order_id'],
            'amount' => number_format($amount, 2, '.', ''),
            'returnUrl' => $payload['returnUrl'] ?? null,
            'cancelUrl' => $payload['cancelUrl'] ?? null
        ];

        return array_filter(array_merge($defaults, $payload), static function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function extractResponsePayload(ResponseInterface $response): array
    {
        $data = $response->getData();
        return is_array($data) ? $data : [];
    }

    private function isDriverAllowed(string $driver): bool
    {
        $normalized = $this->normalizeDriverName($driver);
        foreach ($this->getAvailableDrivers() as $allowed) {
            if ($normalized === $this->normalizeDriverName($allowed)) {
                return true;
            }
        }
        return false;
    }

    private function normalizeDriverName(string $driver): string
    {
        return ltrim($driver, '\\');
    }
}

