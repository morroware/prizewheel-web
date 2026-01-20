<?php
/**
 * Webhook Manager - Trigger external webhooks on prize wheel events
 */

require_once __DIR__ . '/Database.php';

class WebhookManager {
    private $db;

    public function __construct($db = null) {
        $this->db = $db;
    }

    /**
     * Register a webhook
     */
    public function registerWebhook($url, $events, $secret = null) {
        if (!$this->db) {
            return false;
        }

        $data = [
            'url' => $url,
            'events' => json_encode($events),
            'secret' => $secret ?? $this->generateSecret(),
            'is_active' => 1,
            'retry_count' => 3
        ];

        return $this->db->insert('webhooks', $data);
    }

    /**
     * Get all active webhooks
     */
    public function getActiveWebhooks() {
        if (!$this->db) {
            return [];
        }

        return $this->db->fetchAll('SELECT * FROM webhooks WHERE is_active = 1');
    }

    /**
     * Trigger webhooks for an event
     */
    public function trigger($eventType, $payload) {
        if (!$this->db) {
            return false;
        }

        $webhooks = $this->getActiveWebhooks();

        foreach ($webhooks as $webhook) {
            $subscribedEvents = json_decode($webhook['events'], true);

            if (in_array($eventType, $subscribedEvents) || in_array('*', $subscribedEvents)) {
                $this->sendWebhook($webhook, $eventType, $payload);
            }
        }

        return true;
    }

    /**
     * Send webhook with retry logic
     */
    private function sendWebhook($webhook, $eventType, $payload) {
        $data = [
            'event' => $eventType,
            'payload' => $payload,
            'timestamp' => date('c'),
            'webhook_id' => $webhook['id']
        ];

        $signature = hash_hmac('sha256', json_encode($data), $webhook['secret']);

        $ch = curl_init($webhook['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Webhook-Signature: sha256=' . $signature,
                'User-Agent: PrizeWheel-Webhook/1.0'
            ],
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Update last triggered time
        $this->db->update(
            'webhooks',
            ['last_triggered' => date('Y-m-d H:i:s')],
            'id = ?',
            [$webhook['id']]
        );

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * Generate webhook secret
     */
    private function generateSecret($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature($payload, $signature, $secret) {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook($id) {
        if (!$this->db) {
            return false;
        }

        return $this->db->delete('webhooks', 'id = ?', [$id]);
    }

    /**
     * Update webhook
     */
    public function updateWebhook($id, $data) {
        if (!$this->db) {
            return false;
        }

        $updateData = [];

        if (isset($data['url'])) {
            $updateData['url'] = $data['url'];
        }

        if (isset($data['events'])) {
            $updateData['events'] = json_encode($data['events']);
        }

        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'] ? 1 : 0;
        }

        if (isset($data['retry_count'])) {
            $updateData['retry_count'] = $data['retry_count'];
        }

        return $this->db->update('webhooks', $updateData, 'id = ?', [$id]);
    }
}
