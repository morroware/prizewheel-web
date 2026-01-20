<?php
/**
 * Configuration File - Professional Prize Wheel
 *
 * Copy this file to config.local.php and customize for your environment
 * config.local.php will not be tracked by git
 */

// ==================== CORE SETTINGS ====================

// Enable database mode (SQLite/MySQL) for advanced features
// Set to false to use JSON files only
define('USE_DATABASE', true);

// Database type: 'sqlite' or 'mysql'
define('DB_TYPE', 'sqlite');

// SQLite database path
define('DB_PATH', DATA_DIR . 'prizewheel.db');

// MySQL settings (only if DB_TYPE is 'mysql')
define('DB_HOST', 'localhost');
define('DB_NAME', 'prizewheel');
define('DB_USER', 'root');
define('DB_PASS', '');

// ==================== FEATURE FLAGS ====================

// Enable extended API endpoints
define('ENABLE_EXTENDED_API', true);

// Enable analytics and reporting
define('ENABLE_ANALYTICS', true);

// Enable webhooks
define('ENABLE_WEBHOOKS', true);

// Enable multi-wheel support
define('ENABLE_MULTI_WHEEL', true);

// Enable user authentication
define('ENABLE_AUTH', true);

// Enable import/export features
define('ENABLE_IMPORT_EXPORT', true);

// ==================== SECURITY ====================

// JWT secret key for authentication (change this!)
define('JWT_SECRET', 'change-this-to-a-random-secret-key-' . bin2hex(random_bytes(16)));

// API rate limiting (requests per minute)
define('API_RATE_LIMIT', 100);

// Enable CORS (Cross-Origin Resource Sharing)
define('ENABLE_CORS', false);

// Allowed CORS origins (if ENABLE_CORS is true)
define('CORS_ORIGINS', '*'); // Use specific domains in production: 'https://example.com'

// ==================== WEBHOOK SETTINGS ====================

// Webhook timeout in seconds
define('WEBHOOK_TIMEOUT', 10);

// Webhook retry count on failure
define('WEBHOOK_RETRY_COUNT', 3);

// ==================== PERFORMANCE ====================

// Enable caching
define('ENABLE_CACHE', true);

// Cache duration in seconds
define('CACHE_DURATION', 300);

// Maximum file upload size in MB
define('MAX_UPLOAD_SIZE_MB', 10);

// ==================== LOGGING ====================

// Enable debug mode (shows detailed errors)
define('DEBUG_MODE', false);

// Enable event logging
define('ENABLE_LOGGING', true);

// Log file path
define('LOG_FILE', DATA_DIR . 'prizewheel.log');

// Log level: 'error', 'warning', 'info', 'debug'
define('LOG_LEVEL', 'info');

// ==================== EMAIL NOTIFICATIONS ====================

// Enable email notifications for prize wins
define('ENABLE_EMAIL_NOTIFICATIONS', false);

// SMTP settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_FROM_EMAIL', 'noreply@prizewheel.com');
define('SMTP_FROM_NAME', 'Prize Wheel');

// ==================== BACKUP ====================

// Automatic backup interval in hours (0 to disable)
define('AUTO_BACKUP_INTERVAL', 24);

// Maximum number of backups to keep
define('MAX_BACKUP_COUNT', 30);

// ==================== PRIZE SETTINGS ====================

// Default prize configuration
define('DEFAULT_PRIZE_CATEGORY', 'General');
define('DEFAULT_PRIZE_WEIGHT', 1.0);
define('DEFAULT_PRIZE_COLOR', '#4CAF50');

// Enable prize images
define('ENABLE_PRIZE_IMAGES', true);

// Prize image upload directory
define('PRIZE_IMAGE_DIR', STATIC_DIR . 'images/prizes/');

// Maximum prize image size in KB
define('MAX_PRIZE_IMAGE_SIZE_KB', 500);

// ==================== REDEMPTION SETTINGS ====================

// Enable QR code generation for redemptions
define('ENABLE_QR_CODES', true);

// Redemption code length
define('REDEMPTION_CODE_LENGTH', 8);

// Redemption code expiration in days
define('REDEMPTION_EXPIRATION_DAYS', 30);

// ==================== ANALYTICS ====================

// Track IP addresses
define('TRACK_IP_ADDRESSES', true);

// Track user agents
define('TRACK_USER_AGENTS', true);

// Anonymize IP addresses (GDPR compliance)
define('ANONYMIZE_IP_ADDRESSES', true);

// Maximum analytics data retention in days
define('ANALYTICS_RETENTION_DAYS', 365);

// ==================== CUSTOMIZATION ====================

// Enable custom CSS/JS injection
define('ENABLE_CUSTOM_CODE', true);

// Allowed HTML tags in prize descriptions
define('ALLOWED_HTML_TAGS', '<b><i><u><strong><em><br><p><a>');

// ==================== LOCALIZATION ====================

// Default language
define('DEFAULT_LANGUAGE', 'en');

// Available languages
define('AVAILABLE_LANGUAGES', ['en', 'es', 'fr', 'de']);

// Timezone
define('DEFAULT_TIMEZONE', 'America/New_York');

// Date format
define('DATE_FORMAT', 'Y-m-d H:i:s');

// ==================== INTEGRATION ====================

// Stripe API key (for payment integration)
define('STRIPE_API_KEY', '');
define('STRIPE_PUBLISHABLE_KEY', '');

// Google Analytics tracking ID
define('GOOGLE_ANALYTICS_ID', '');

// Facebook Pixel ID
define('FACEBOOK_PIXEL_ID', '');

// ==================== DEVELOPMENT ====================

// Show SQL queries in debug mode
define('DEBUG_SQL', false);

// Enable profiling
define('ENABLE_PROFILING', false);

// Mock external services in development
define('MOCK_EXTERNAL_SERVICES', false);

// ==================== HELPER FUNCTIONS ====================

/**
 * Get configuration value
 */
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Log message
 */
function logMessage($level, $message, $context = []) {
    if (!ENABLE_LOGGING) {
        return;
    }

    $levels = ['error' => 1, 'warning' => 2, 'info' => 3, 'debug' => 4];
    $currentLevel = $levels[LOG_LEVEL] ?? 3;
    $messageLevel = $levels[$level] ?? 3;

    if ($messageLevel > $currentLevel) {
        return;
    }

    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logLine = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";

    error_log($logLine, 3, LOG_FILE);
}

/**
 * Check if feature is enabled
 */
function isFeatureEnabled($feature) {
    return getConfig('ENABLE_' . strtoupper($feature), false);
}

/**
 * Get database configuration
 */
function getDatabaseConfig() {
    if (DB_TYPE === 'mysql') {
        return [
            'type' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASS
        ];
    }

    return [
        'type' => 'sqlite',
        'path' => DB_PATH
    ];
}

// ==================== TIMEZONE SETUP ====================
date_default_timezone_set(DEFAULT_TIMEZONE);
