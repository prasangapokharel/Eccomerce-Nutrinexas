<?php

namespace App\Models;

use App\Core\Model;

class GatewayTransaction extends Model
{
    protected $table = 'gateway_transactions';
    protected $primaryKey = 'id';

    public function createPending(array $data): int
    {
        $sql = "INSERT INTO {$this->table}
            (order_id, gateway_slug, driver, status, provider_reference, request_payload, response_payload, metadata, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $payload = [
            $data['order_id'],
            $data['gateway_slug'],
            $data['driver'],
            $data['status'] ?? 'pending',
            $data['provider_reference'] ?? null,
            json_encode($data['request_payload'] ?? []),
            json_encode($data['response_payload'] ?? []),
            json_encode($data['metadata'] ?? [])
        ];

        $this->db->query($sql)
            ->bind($payload)
            ->execute();

        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status, array $data = []): void
    {
        $sql = "UPDATE {$this->table}
                SET status = ?, provider_reference = COALESCE(?, provider_reference),
                    response_payload = COALESCE(?, response_payload),
                    updated_at = NOW()
                WHERE id = ?";

        $responsePayload = isset($data['response_payload']) ? json_encode($data['response_payload']) : null;

        $this->db->query($sql)
            ->bind([
                $status,
                $data['provider_reference'] ?? null,
                $responsePayload,
                $id
            ])
            ->execute();
    }

    public function findByReference(string $reference): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE provider_reference = ? LIMIT 1";
        $result = $this->db->query($sql)
            ->bind([$reference])
            ->single();
        return $result ?: null;
    }
}

