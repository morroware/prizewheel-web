<?php
/**
 * Wheel Manager - Multi-wheel support and campaign management
 */

require_once __DIR__ . '/Database.php';

class WheelManager {
    private $db;

    public function __construct($db = null) {
        $this->db = $db;
    }

    /**
     * Create a new wheel
     */
    public function createWheel($data) {
        if (!$this->db) {
            return false;
        }

        $wheelData = [
            'uuid' => $data['uuid'] ?? uniqid('wheel_'),
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->generateSlug($data['name']),
            'description' => $data['description'] ?? '',
            'customization' => isset($data['customization']) ? json_encode($data['customization']) : null,
            'is_active' => $data['is_active'] ?? 1,
            'campaign_start' => $data['campaign_start'] ?? null,
            'campaign_end' => $data['campaign_end'] ?? null,
            'max_spins_per_user' => $data['max_spins_per_user'] ?? null,
            'cooldown_seconds' => $data['cooldown_seconds'] ?? 3,
            'requires_auth' => $data['requires_auth'] ?? 0,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
        ];

        return $this->db->insert('wheels', $wheelData);
    }

    /**
     * Get wheel by slug
     */
    public function getWheelBySlug($slug) {
        if (!$this->db) {
            return null;
        }

        return $this->db->fetchOne('SELECT * FROM wheels WHERE slug = ?', [$slug]);
    }

    /**
     * Get all wheels
     */
    public function getAllWheels($activeOnly = false) {
        if (!$this->db) {
            return [];
        }

        $sql = 'SELECT * FROM wheels';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY created_at DESC';

        return $this->db->fetchAll($sql);
    }

    /**
     * Get active wheels (within campaign dates)
     */
    public function getActiveWheels() {
        if (!$this->db) {
            return [];
        }

        $now = date('Y-m-d H:i:s');
        $sql = "
            SELECT * FROM wheels
            WHERE is_active = 1
              AND (campaign_start IS NULL OR campaign_start <= ?)
              AND (campaign_end IS NULL OR campaign_end >= ?)
            ORDER BY created_at DESC
        ";

        return $this->db->fetchAll($sql, [$now, $now]);
    }

    /**
     * Update wheel
     */
    public function updateWheel($id, $data) {
        if (!$this->db) {
            return false;
        }

        $updateData = [];
        $allowedFields = [
            'name', 'slug', 'description', 'is_active',
            'campaign_start', 'campaign_end', 'max_spins_per_user',
            'cooldown_seconds', 'requires_auth'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['customization'])) {
            $updateData['customization'] = json_encode($data['customization']);
        }

        if (isset($data['metadata'])) {
            $updateData['metadata'] = json_encode($data['metadata']);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->update('wheels', $updateData, 'id = ?', [$id]);
    }

    /**
     * Delete wheel
     */
    public function deleteWheel($id) {
        if (!$this->db) {
            return false;
        }

        // Note: This doesn't cascade delete prizes/spins for safety
        return $this->db->delete('wheels', 'id = ?', [$id]);
    }

    /**
     * Clone wheel with all prizes
     */
    public function cloneWheel($id, $newName) {
        if (!$this->db) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $originalWheel = $this->db->fetchOne('SELECT * FROM wheels WHERE id = ?', [$id]);

            if (!$originalWheel) {
                throw new Exception('Wheel not found');
            }

            // Create new wheel
            $newWheelData = [
                'uuid' => uniqid('wheel_'),
                'name' => $newName,
                'slug' => $this->generateSlug($newName),
                'description' => $originalWheel['description'],
                'customization' => $originalWheel['customization'],
                'is_active' => 0,
                'max_spins_per_user' => $originalWheel['max_spins_per_user'],
                'cooldown_seconds' => $originalWheel['cooldown_seconds'],
                'requires_auth' => $originalWheel['requires_auth'],
                'metadata' => $originalWheel['metadata']
            ];

            $newWheelId = $this->db->insert('wheels', $newWheelData);
            $newWheelUuid = $newWheelData['uuid'];

            // Clone prizes
            $prizes = $this->db->fetchAll('SELECT * FROM prizes WHERE wheel_id = ?', [$originalWheel['uuid']]);

            foreach ($prizes as $prize) {
                $newPrize = $prize;
                unset($newPrize['id']);
                $newPrize['uuid'] = uniqid('prize_');
                $newPrize['wheel_id'] = $newWheelUuid;
                unset($newPrize['created_at']);
                unset($newPrize['updated_at']);

                $this->db->insert('prizes', $newPrize);
            }

            $this->db->commit();

            return $newWheelId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get user spin count for wheel
     */
    public function getUserSpinCount($wheelId, $userId) {
        if (!$this->db) {
            return 0;
        }

        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as count FROM spin_history WHERE wheel_id = ? AND user_id = ?',
            [$wheelId, $userId]
        );

        return $result['count'] ?? 0;
    }

    /**
     * Check if user can spin
     */
    public function canUserSpin($wheelId, $userId) {
        if (!$this->db) {
            return true;
        }

        $wheel = $this->db->fetchOne('SELECT * FROM wheels WHERE uuid = ?', [$wheelId]);

        if (!$wheel) {
            return false;
        }

        // Check if wheel is active
        if (!$wheel['is_active']) {
            return false;
        }

        // Check campaign dates
        $now = date('Y-m-d H:i:s');
        if ($wheel['campaign_start'] && $wheel['campaign_start'] > $now) {
            return false;
        }
        if ($wheel['campaign_end'] && $wheel['campaign_end'] < $now) {
            return false;
        }

        // Check max spins per user
        if ($wheel['max_spins_per_user']) {
            $spinCount = $this->getUserSpinCount($wheelId, $userId);
            if ($spinCount >= $wheel['max_spins_per_user']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate URL-friendly slug
     */
    private function generateSlug($name) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

        // Ensure uniqueness
        if ($this->db) {
            $counter = 1;
            $originalSlug = $slug;
            while ($this->db->fetchOne('SELECT id FROM wheels WHERE slug = ?', [$slug])) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        return $slug;
    }

    /**
     * Get wheel statistics
     */
    public function getWheelStats($wheelId) {
        if (!$this->db) {
            return [];
        }

        $stats = $this->db->fetchOne("
            SELECT
                COUNT(*) as total_spins,
                SUM(CASE WHEN is_winner = 1 THEN 1 ELSE 0 END) as total_wins,
                COUNT(DISTINCT session_id) as unique_sessions,
                COUNT(DISTINCT user_id) as unique_users
            FROM spin_history
            WHERE wheel_id = ?
        ", [$wheelId]);

        $prizeCount = $this->db->fetchOne("
            SELECT COUNT(*) as count FROM prizes WHERE wheel_id = ? AND enabled = 1
        ", [$wheelId]);

        $stats['active_prizes'] = $prizeCount['count'] ?? 0;

        if ($stats['total_spins'] > 0) {
            $stats['win_rate'] = round(($stats['total_wins'] / $stats['total_spins']) * 100, 2);
        } else {
            $stats['win_rate'] = 0;
        }

        return $stats;
    }
}
