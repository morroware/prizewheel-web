<?php
/**
 * Database Layer - SQLite/MySQL support for Prize Wheel
 * Provides advanced data persistence beyond JSON files
 */

class Database {
    private $pdo;
    private $type; // 'sqlite' or 'mysql'

    public function __construct($config = []) {
        $this->type = $config['type'] ?? 'sqlite';

        if ($this->type === 'sqlite') {
            $dbPath = $config['path'] ?? DATA_DIR . 'prizewheel.db';
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } elseif ($this->type === 'mysql') {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
                $config['host'] ?? 'localhost',
                $config['database'] ?? 'prizewheel'
            );
            $this->pdo = new PDO($dsn, $config['username'] ?? 'root', $config['password'] ?? '');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        $this->initSchema();
    }

    private function initSchema() {
        // Prizes table with enhanced features
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS prizes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                wheel_id TEXT DEFAULT 'default',
                name TEXT NOT NULL,
                description TEXT,
                category TEXT,
                tags TEXT,
                weight REAL DEFAULT 1.0,
                color TEXT DEFAULT '#4CAF50',
                is_winner INTEGER DEFAULT 1,
                sound_path TEXT,
                image_url TEXT,
                icon TEXT,
                enabled INTEGER DEFAULT 1,
                stock_enabled INTEGER DEFAULT 0,
                stock_total INTEGER DEFAULT 0,
                stock_remaining INTEGER DEFAULT 0,
                start_date TEXT,
                end_date TEXT,
                redemption_code TEXT,
                redemption_url TEXT,
                metadata TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Spin history with enhanced tracking
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS spin_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                spin_id TEXT UNIQUE NOT NULL,
                wheel_id TEXT DEFAULT 'default',
                session_id TEXT,
                user_id TEXT,
                prize_id INTEGER,
                prize_uuid TEXT,
                prize_name TEXT,
                is_winner INTEGER,
                ip_address TEXT,
                user_agent TEXT,
                metadata TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (prize_id) REFERENCES prizes(id)
            )
        ");

        // Users table for authentication
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                permissions TEXT,
                api_key TEXT UNIQUE,
                last_login TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Wheels configuration for multi-wheel support
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS wheels (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                description TEXT,
                customization TEXT,
                is_active INTEGER DEFAULT 1,
                campaign_start TEXT,
                campaign_end TEXT,
                max_spins_per_user INTEGER,
                cooldown_seconds INTEGER DEFAULT 3,
                requires_auth INTEGER DEFAULT 0,
                metadata TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Analytics events
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS analytics_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_type TEXT NOT NULL,
                wheel_id TEXT,
                prize_id INTEGER,
                session_id TEXT,
                user_id TEXT,
                event_data TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // API keys for authentication
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS api_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key_hash TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                permissions TEXT,
                rate_limit INTEGER DEFAULT 100,
                expires_at TEXT,
                last_used TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Webhooks
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS webhooks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                url TEXT NOT NULL,
                events TEXT NOT NULL,
                secret TEXT,
                is_active INTEGER DEFAULT 1,
                retry_count INTEGER DEFAULT 3,
                last_triggered TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Redemptions tracking
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS redemptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT UNIQUE NOT NULL,
                spin_id TEXT,
                prize_id INTEGER,
                user_id TEXT,
                redeemed INTEGER DEFAULT 0,
                redeemed_at TEXT,
                expires_at TEXT,
                metadata TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (prize_id) REFERENCES prizes(id)
            )
        ");

        // Create indexes for performance
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_prizes_wheel ON prizes(wheel_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_prizes_enabled ON prizes(enabled)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_spin_history_wheel ON spin_history(wheel_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_spin_history_session ON spin_history(session_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_analytics_type ON analytics_events(event_type)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_redemptions_code ON redemptions(code)");
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        $values = [];

        foreach ($data as $field => $value) {
            $setParts[] = "$field = ?";
            $values[] = $value;
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setParts),
            $where
        );

        return $this->query($sql, array_merge($values, $whereParams));
    }

    public function delete($table, $where, $whereParams = []) {
        $sql = sprintf("DELETE FROM %s WHERE %s", $table, $where);
        return $this->query($sql, $whereParams);
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollBack();
    }

    public function getPDO() {
        return $this->pdo;
    }
}
