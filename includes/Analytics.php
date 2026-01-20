<?php
/**
 * Analytics Engine - Track and analyze prize wheel performance
 */

require_once __DIR__ . '/Database.php';

class Analytics {
    private $db;

    public function __construct($db = null) {
        $this->db = $db;
    }

    /**
     * Track an event
     */
    public function trackEvent($type, $data = []) {
        if (!$this->db) {
            return false;
        }

        $eventData = [
            'event_type' => $type,
            'wheel_id' => $data['wheel_id'] ?? 'default',
            'prize_id' => $data['prize_id'] ?? null,
            'session_id' => $data['session_id'] ?? session_id(),
            'user_id' => $data['user_id'] ?? null,
            'event_data' => json_encode($data)
        ];

        return $this->db->insert('analytics_events', $eventData);
    }

    /**
     * Get spin statistics
     */
    public function getSpinStats($wheelId = 'default', $dateFrom = null, $dateTo = null) {
        $sql = "SELECT
            COUNT(*) as total_spins,
            SUM(CASE WHEN is_winner = 1 THEN 1 ELSE 0 END) as total_wins,
            COUNT(DISTINCT session_id) as unique_sessions,
            COUNT(DISTINCT DATE(created_at)) as active_days
        FROM spin_history
        WHERE wheel_id = ?";

        $params = [$wheelId];

        if ($dateFrom) {
            $sql .= " AND created_at >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND created_at <= ?";
            $params[] = $dateTo;
        }

        $stats = $this->db->fetchOne($sql, $params);

        if ($stats && $stats['total_spins'] > 0) {
            $stats['win_rate'] = round(($stats['total_wins'] / $stats['total_spins']) * 100, 2);
            $stats['avg_spins_per_session'] = round($stats['total_spins'] / max($stats['unique_sessions'], 1), 2);
        }

        return $stats;
    }

    /**
     * Get prize performance
     */
    public function getPrizePerformance($wheelId = 'default', $dateFrom = null, $dateTo = null) {
        $sql = "
            SELECT
                p.id,
                p.name,
                p.category,
                p.is_winner,
                COUNT(sh.id) as spin_count,
                (COUNT(sh.id) * 100.0 / (SELECT COUNT(*) FROM spin_history WHERE wheel_id = ?)) as percentage
            FROM prizes p
            LEFT JOIN spin_history sh ON p.id = sh.prize_id
            WHERE p.wheel_id = ?
        ";

        $params = [$wheelId, $wheelId];

        if ($dateFrom) {
            $sql .= " AND sh.created_at >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND sh.created_at <= ?";
            $params[] = $dateTo;
        }

        $sql .= " GROUP BY p.id ORDER BY spin_count DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get spins over time (for charts)
     */
    public function getSpinsOverTime($wheelId = 'default', $interval = 'day', $limit = 30) {
        $dateFormat = $interval === 'hour' ? '%Y-%m-%d %H:00' : '%Y-%m-%d';

        $sql = "
            SELECT
                strftime(?, created_at) as period,
                COUNT(*) as total,
                SUM(CASE WHEN is_winner = 1 THEN 1 ELSE 0 END) as wins
            FROM spin_history
            WHERE wheel_id = ?
            GROUP BY period
            ORDER BY period DESC
            LIMIT ?
        ";

        return array_reverse($this->db->fetchAll($sql, [$dateFormat, $wheelId, $limit]));
    }

    /**
     * Get top winners
     */
    public function getTopWinners($wheelId = 'default', $limit = 10) {
        $sql = "
            SELECT
                session_id,
                user_id,
                COUNT(*) as total_spins,
                SUM(CASE WHEN is_winner = 1 THEN 1 ELSE 0 END) as wins,
                MAX(created_at) as last_spin
            FROM spin_history
            WHERE wheel_id = ?
            GROUP BY session_id, user_id
            ORDER BY wins DESC, total_spins DESC
            LIMIT ?
        ";

        return $this->db->fetchAll($sql, [$wheelId, $limit]);
    }

    /**
     * Get hourly activity heatmap
     */
    public function getHourlyActivity($wheelId = 'default') {
        $sql = "
            SELECT
                CAST(strftime('%H', created_at) AS INTEGER) as hour,
                COUNT(*) as count
            FROM spin_history
            WHERE wheel_id = ?
            GROUP BY hour
            ORDER BY hour
        ";

        $results = $this->db->fetchAll($sql, [$wheelId]);

        // Fill in missing hours with 0
        $activity = array_fill(0, 24, 0);
        foreach ($results as $row) {
            $activity[$row['hour']] = $row['count'];
        }

        return $activity;
    }

    /**
     * Get category breakdown
     */
    public function getCategoryBreakdown($wheelId = 'default') {
        $sql = "
            SELECT
                COALESCE(p.category, 'Uncategorized') as category,
                COUNT(sh.id) as spins,
                SUM(CASE WHEN sh.is_winner = 1 THEN 1 ELSE 0 END) as wins
            FROM prizes p
            LEFT JOIN spin_history sh ON p.id = sh.prize_id
            WHERE p.wheel_id = ?
            GROUP BY p.category
            ORDER BY spins DESC
        ";

        return $this->db->fetchAll($sql, [$wheelId]);
    }

    /**
     * Get conversion funnel
     */
    public function getConversionFunnel($wheelId = 'default', $dateFrom = null, $dateTo = null) {
        $params = [$wheelId];
        $dateFilter = '';

        if ($dateFrom) {
            $dateFilter .= " AND created_at >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $dateFilter .= " AND created_at <= ?";
            $params[] = $dateTo;
        }

        // Page views (from analytics events)
        $views = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM analytics_events WHERE event_type = 'page_view' AND wheel_id = ? $dateFilter",
            $params
        );

        // Spins
        $spins = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM spin_history WHERE wheel_id = ? $dateFilter",
            $params
        );

        // Wins
        $wins = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM spin_history WHERE wheel_id = ? AND is_winner = 1 $dateFilter",
            $params
        );

        // Redemptions
        $redemptions = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM redemptions r
             JOIN spin_history sh ON r.spin_id = sh.spin_id
             WHERE sh.wheel_id = ? AND r.redeemed = 1 $dateFilter",
            $params
        );

        return [
            'views' => $views['count'] ?? 0,
            'spins' => $spins['count'] ?? 0,
            'wins' => $wins['count'] ?? 0,
            'redemptions' => $redemptions['count'] ?? 0,
            'view_to_spin_rate' => $views['count'] > 0 ? round(($spins['count'] / $views['count']) * 100, 2) : 0,
            'spin_to_win_rate' => $spins['count'] > 0 ? round(($wins['count'] / $spins['count']) * 100, 2) : 0,
            'win_to_redemption_rate' => $wins['count'] > 0 ? round(($redemptions['count'] / $wins['count']) * 100, 2) : 0
        ];
    }

    /**
     * Export analytics data as CSV
     */
    public function exportToCsv($wheelId = 'default', $dateFrom = null, $dateTo = null) {
        $sql = "
            SELECT
                sh.spin_id,
                sh.created_at,
                p.name as prize_name,
                p.category,
                sh.is_winner,
                sh.session_id,
                sh.user_id
            FROM spin_history sh
            LEFT JOIN prizes p ON sh.prize_id = p.id
            WHERE sh.wheel_id = ?
        ";

        $params = [$wheelId];

        if ($dateFrom) {
            $sql .= " AND sh.created_at >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND sh.created_at <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY sh.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }
}
