<?php
/**
 * Authentication Manager - API keys, rate limiting, JWT support
 */

require_once __DIR__ . '/Database.php';

class AuthManager {
    private $db;
    private $rateLimitWindow = 60; // seconds

    public function __construct($db = null) {
        $this->db = $db;
    }

    /**
     * Generate API key
     */
    public function generateApiKey($name, $permissions = [], $rateLimit = 100, $expiresAt = null) {
        if (!$this->db) {
            return false;
        }

        $apiKey = 'pk_' . bin2hex(random_bytes(24));
        $keyHash = hash('sha256', $apiKey);

        $data = [
            'key_hash' => $keyHash,
            'name' => $name,
            'permissions' => json_encode($permissions),
            'rate_limit' => $rateLimit,
            'expires_at' => $expiresAt
        ];

        $id = $this->db->insert('api_keys', $data);

        return [
            'id' => $id,
            'key' => $apiKey,
            'name' => $name,
            'permissions' => $permissions,
            'rate_limit' => $rateLimit
        ];
    }

    /**
     * Validate API key
     */
    public function validateApiKey($apiKey) {
        if (!$this->db) {
            return false;
        }

        $keyHash = hash('sha256', $apiKey);
        $key = $this->db->fetchOne(
            'SELECT * FROM api_keys WHERE key_hash = ?',
            [$keyHash]
        );

        if (!$key) {
            return false;
        }

        // Check expiration
        if ($key['expires_at'] && strtotime($key['expires_at']) < time()) {
            return false;
        }

        // Update last used
        $this->db->update(
            'api_keys',
            ['last_used' => date('Y-m-d H:i:s')],
            'id = ?',
            [$key['id']]
        );

        return [
            'id' => $key['id'],
            'name' => $key['name'],
            'permissions' => json_decode($key['permissions'], true),
            'rate_limit' => $key['rate_limit']
        ];
    }

    /**
     * Check rate limit
     */
    public function checkRateLimit($identifier, $limit) {
        if (!$this->db) {
            return true;
        }

        $cacheKey = 'rate_limit_' . $identifier;
        $now = time();

        // Get recent requests from analytics
        $count = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM analytics_events
             WHERE event_type = 'api_request'
               AND event_data LIKE ?
               AND created_at >= datetime('now', '-{$this->rateLimitWindow} seconds')",
            ['%' . $identifier . '%']
        );

        return ($count['count'] ?? 0) < $limit;
    }

    /**
     * Record API request for rate limiting
     */
    public function recordApiRequest($identifier, $endpoint) {
        if (!$this->db) {
            return false;
        }

        $eventData = [
            'event_type' => 'api_request',
            'event_data' => json_encode([
                'identifier' => $identifier,
                'endpoint' => $endpoint,
                'timestamp' => time()
            ])
        ];

        return $this->db->insert('analytics_events', $eventData);
    }

    /**
     * Revoke API key
     */
    public function revokeApiKey($id) {
        if (!$this->db) {
            return false;
        }

        return $this->db->delete('api_keys', 'id = ?', [$id]);
    }

    /**
     * List all API keys
     */
    public function listApiKeys() {
        if (!$this->db) {
            return [];
        }

        return $this->db->fetchAll(
            'SELECT id, name, permissions, rate_limit, expires_at, last_used, created_at FROM api_keys ORDER BY created_at DESC'
        );
    }

    /**
     * Create user account
     */
    public function createUser($username, $email, $password, $role = 'user') {
        if (!$this->db) {
            return false;
        }

        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        $apiKey = 'usr_' . bin2hex(random_bytes(16));

        $userData = [
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
            'api_key' => $apiKey,
            'permissions' => json_encode([])
        ];

        return $this->db->insert('users', $userData);
    }

    /**
     * Authenticate user
     */
    public function authenticateUser($username, $password) {
        if (!$this->db) {
            return false;
        }

        $user = $this->db->fetchOne(
            'SELECT * FROM users WHERE username = ? OR email = ?',
            [$username, $username]
        );

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Update last login
        $this->db->update(
            'users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = ?',
            [$user['id']]
        );

        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'permissions' => json_decode($user['permissions'], true),
            'api_key' => $user['api_key']
        ];
    }

    /**
     * Generate JWT token
     */
    public function generateJWT($payload, $secretKey, $expiresIn = 3600) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + $expiresIn;
        $payload['iat'] = time();

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Verify JWT token
     */
    public function verifyJWT($jwt, $secretKey) {
        $tokenParts = explode('.', $jwt);

        if (count($tokenParts) !== 3) {
            return false;
        }

        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        if ($base64UrlSignature !== $signatureProvided) {
            return false;
        }

        $payloadData = json_decode($payload, true);

        // Check expiration
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return false;
        }

        return $payloadData;
    }

    private function base64UrlEncode($text) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }

    /**
     * Check permission
     */
    public function hasPermission($user, $permission) {
        if ($user['role'] === 'admin') {
            return true;
        }

        $permissions = $user['permissions'] ?? [];
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }
}
