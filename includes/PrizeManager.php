<?php
/**
 * Prize Manager - Advanced prize management with inventory, scheduling, categories
 */

require_once __DIR__ . '/Database.php';

class PrizeManager {
    private $db;

    public function __construct($db = null) {
        $this->db = $db;
    }

    /**
     * Get all prizes with optional filtering
     */
    public function getPrizes($wheelId = 'default', $filters = []) {
        if (!$this->db) {
            return getPrizes(); // Fall back to JSON
        }

        $conditions = ['wheel_id = ?'];
        $params = [$wheelId];

        if (isset($filters['enabled'])) {
            $conditions[] = 'enabled = ?';
            $params[] = $filters['enabled'] ? 1 : 0;
        }

        if (isset($filters['category'])) {
            $conditions[] = 'category = ?';
            $params[] = $filters['category'];
        }

        if (isset($filters['search'])) {
            $conditions[] = '(name LIKE ? OR description LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        // Check date availability
        $now = date('Y-m-d H:i:s');
        if (!isset($filters['ignore_dates']) || !$filters['ignore_dates']) {
            $conditions[] = '(start_date IS NULL OR start_date <= ?)';
            $conditions[] = '(end_date IS NULL OR end_date >= ?)';
            $params[] = $now;
            $params[] = $now;
        }

        $sql = "SELECT * FROM prizes WHERE " . implode(' AND ', $conditions) . " ORDER BY name";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get enabled prizes for spinning (with stock and schedule checks)
     */
    public function getAvailablePrizes($wheelId = 'default') {
        if (!$this->db) {
            $prizes = getPrizes();
            return array_filter($prizes, function($p) {
                return isset($p['enabled']) && $p['enabled'];
            });
        }

        $now = date('Y-m-d H:i:s');
        $sql = "
            SELECT * FROM prizes
            WHERE wheel_id = ?
              AND enabled = 1
              AND (start_date IS NULL OR start_date <= ?)
              AND (end_date IS NULL OR end_date >= ?)
              AND (stock_enabled = 0 OR stock_remaining > 0)
            ORDER BY name
        ";

        return $this->db->fetchAll($sql, [$wheelId, $now, $now]);
    }

    /**
     * Create a new prize
     */
    public function createPrize($data) {
        if (!$this->db) {
            // Fall back to JSON
            $prizes = getPrizes();
            $newPrize = [
                'id' => uniqid(),
                'name' => $data['name'] ?? 'New Prize',
                'description' => $data['description'] ?? '',
                'weight' => floatval($data['weight'] ?? 1.0),
                'color' => $data['color'] ?? '#4CAF50',
                'is_winner' => $data['is_winner'] ?? true,
                'sound_path' => $data['sound_path'] ?? '',
                'enabled' => $data['enabled'] ?? true
            ];
            $prizes[] = $newPrize;
            savePrizes($prizes);
            return $newPrize;
        }

        $prizeData = [
            'uuid' => $data['uuid'] ?? uniqid('prize_'),
            'wheel_id' => $data['wheel_id'] ?? 'default',
            'name' => $data['name'] ?? 'New Prize',
            'description' => $data['description'] ?? '',
            'category' => $data['category'] ?? null,
            'tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
            'weight' => floatval($data['weight'] ?? 1.0),
            'color' => $data['color'] ?? '#4CAF50',
            'is_winner' => $data['is_winner'] ?? 1,
            'sound_path' => $data['sound_path'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'icon' => $data['icon'] ?? null,
            'enabled' => $data['enabled'] ?? 1,
            'stock_enabled' => $data['stock_enabled'] ?? 0,
            'stock_total' => $data['stock_total'] ?? 0,
            'stock_remaining' => $data['stock_remaining'] ?? 0,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'redemption_code' => $data['redemption_code'] ?? null,
            'redemption_url' => $data['redemption_url'] ?? null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
        ];

        $id = $this->db->insert('prizes', $prizeData);
        $prizeData['id'] = $id;
        return $prizeData;
    }

    /**
     * Update a prize
     */
    public function updatePrize($id, $data) {
        if (!$this->db) {
            // Fall back to JSON
            $prizes = getPrizes();
            foreach ($prizes as &$prize) {
                if ($prize['id'] === $id) {
                    $prize = array_merge($prize, $data);
                    savePrizes($prizes);
                    return $prize;
                }
            }
            return null;
        }

        $updateData = [];
        $allowedFields = [
            'name', 'description', 'category', 'weight', 'color',
            'is_winner', 'sound_path', 'image_url', 'icon', 'enabled',
            'stock_enabled', 'stock_total', 'stock_remaining',
            'start_date', 'end_date', 'redemption_code', 'redemption_url'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['tags'])) {
            $updateData['tags'] = json_encode($data['tags']);
        }

        if (isset($data['metadata'])) {
            $updateData['metadata'] = json_encode($data['metadata']);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $this->db->update('prizes', $updateData, 'id = ?', [$id]);
        return $this->getPrizeById($id);
    }

    /**
     * Delete a prize
     */
    public function deletePrize($id) {
        if (!$this->db) {
            // Fall back to JSON
            $prizes = getPrizes();
            $prizes = array_filter($prizes, function($p) use ($id) {
                return $p['id'] !== $id;
            });
            savePrizes(array_values($prizes));
            return true;
        }

        $this->db->delete('prizes', 'id = ?', [$id]);
        return true;
    }

    /**
     * Get prize by ID
     */
    public function getPrizeById($id) {
        if (!$this->db) {
            $prizes = getPrizes();
            foreach ($prizes as $prize) {
                if ($prize['id'] === $id) {
                    return $prize;
                }
            }
            return null;
        }

        return $this->db->fetchOne('SELECT * FROM prizes WHERE id = ?', [$id]);
    }

    /**
     * Decrement prize stock after winning
     */
    public function decrementStock($id) {
        if (!$this->db) {
            return true; // JSON doesn't support stock
        }

        $this->db->query(
            'UPDATE prizes SET stock_remaining = stock_remaining - 1 WHERE id = ? AND stock_enabled = 1',
            [$id]
        );

        return true;
    }

    /**
     * Get prize categories
     */
    public function getCategories($wheelId = 'default') {
        if (!$this->db) {
            return [];
        }

        $sql = "SELECT DISTINCT category FROM prizes WHERE wheel_id = ? AND category IS NOT NULL ORDER BY category";
        $results = $this->db->fetchAll($sql, [$wheelId]);
        return array_column($results, 'category');
    }

    /**
     * Import prizes from array
     */
    public function importPrizes($prizesArray, $wheelId = 'default') {
        $imported = 0;
        $errors = [];

        foreach ($prizesArray as $prizeData) {
            try {
                $prizeData['wheel_id'] = $wheelId;
                $this->createPrize($prizeData);
                $imported++;
            } catch (Exception $e) {
                $errors[] = [
                    'prize' => $prizeData['name'] ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors
        ];
    }

    /**
     * Generate redemption code
     */
    public function generateRedemptionCode($length = 8) {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }
}
