<?php
/**
 * Extended API Endpoints - Professional Features
 * Include this file after the main routing in index.php
 *
 * To enable: Add at the end of index.php before the 404 handler:
 * if (strpos($path, '/api/') === 0) {
 *     require_once __DIR__ . '/api_extended.php';
 * }
 */

// Load professional managers
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/PrizeManager.php';
require_once __DIR__ . '/includes/Analytics.php';
require_once __DIR__ . '/includes/WebhookManager.php';
require_once __DIR__ . '/includes/WheelManager.php';
require_once __DIR__ . '/includes/AuthManager.php';
require_once __DIR__ . '/includes/ImportExport.php';

// Initialize database if enabled
$db = null;
$USE_DATABASE = defined('USE_DATABASE') && USE_DATABASE;

if ($USE_DATABASE) {
    try {
        $dbConfig = [
            'type' => defined('DB_TYPE') ? DB_TYPE : 'sqlite',
            'path' => defined('DB_PATH') ? DB_PATH : DATA_DIR . 'prizewheel.db'
        ];
        $db = new Database($dbConfig);
    } catch (Exception $e) {
        error_log('Database initialization failed: ' . $e->getMessage());
        $db = null;
    }
}

// Initialize managers
$prizeManager = new PrizeManager($db);
$analytics = new Analytics($db);
$webhookManager = new WebhookManager($db);
$wheelManager = new WheelManager($db);
$authManager = new AuthManager($db);
$importExport = new ImportExport($db);

// Authentication middleware
function requireAuth($authManager) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $token = $matches[1];

        // Check if it's an API key
        if (strpos($token, 'pk_') === 0) {
            $user = $authManager->validateApiKey($token);
            if ($user) {
                return $user;
            }
        }
    }

    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

// ==================== ENHANCED PRIZES ====================

// GET /api/prizes/enhanced - Get prizes with filters
if ($path === '/api/prizes/enhanced' && $requestMethod === 'GET') {
    $wheelId = $_GET['wheel_id'] ?? 'default';
    $filters = [];

    if (isset($_GET['enabled'])) {
        $filters['enabled'] = $_GET['enabled'] === 'true';
    }

    if (isset($_GET['category'])) {
        $filters['category'] = $_GET['category'];
    }

    if (isset($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }

    $prizes = $prizeManager->getPrizes($wheelId, $filters);
    jsonResponse(['success' => true, 'prizes' => $prizes]);
}

// POST /api/prizes/enhanced - Create prize with advanced features
if ($path === '/api/prizes/enhanced' && $requestMethod === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $prize = $prizeManager->createPrize($input);
    jsonResponse(['success' => true, 'prize' => $prize]);
}

// PUT /api/prizes/enhanced/{id} - Update prize
if (preg_match('#^/api/prizes/enhanced/([^/]+)$#', $path, $matches) && $requestMethod === 'PUT') {
    $prizeId = $matches[1];
    $input = json_decode(file_get_contents('php://input'), true);
    $prize = $prizeManager->updatePrize($prizeId, $input);

    if ($prize) {
        jsonResponse(['success' => true, 'prize' => $prize]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Prize not found'], 404);
    }
}

// GET /api/prizes/categories - Get all categories
if ($path === '/api/prizes/categories' && $requestMethod === 'GET') {
    $wheelId = $_GET['wheel_id'] ?? 'default';
    $categories = $prizeManager->getCategories($wheelId);
    jsonResponse(['success' => true, 'categories' => $categories]);
}

// ==================== ANALYTICS ====================

// GET /api/analytics/stats - Get spin statistics
if ($path === '/api/analytics/stats' && $requestMethod === 'GET') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $wheelId = $_GET['wheel_id'] ?? 'default';
    $dateFrom = $_GET['from'] ?? null;
    $dateTo = $_GET['to'] ?? null;

    $stats = $analytics->getSpinStats($wheelId, $dateFrom, $dateTo);
    jsonResponse(['success' => true, 'stats' => $stats]);
}

// GET /api/analytics/prizes - Get prize performance
if ($path === '/api/analytics/prizes' && $requestMethod === 'GET') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $wheelId = $_GET['wheel_id'] ?? 'default';
    $dateFrom = $_GET['from'] ?? null;
    $dateTo = $_GET['to'] ?? null;

    $performance = $analytics->getPrizePerformance($wheelId, $dateFrom, $dateTo);
    jsonResponse(['success' => true, 'prizes' => $performance]);
}

// GET /api/analytics/timeline - Get spins over time
if ($path === '/api/analytics/timeline' && $requestMethod === 'GET') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $wheelId = $_GET['wheel_id'] ?? 'default';
    $interval = $_GET['interval'] ?? 'day';
    $limit = intval($_GET['limit'] ?? 30);

    $timeline = $analytics->getSpinsOverTime($wheelId, $interval, $limit);
    jsonResponse(['success' => true, 'timeline' => $timeline]);
}

// GET /api/analytics/categories - Get category breakdown
if ($path === '/api/analytics/categories' && $requestMethod === 'GET') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $wheelId = $_GET['wheel_id'] ?? 'default';
    $breakdown = $analytics->getCategoryBreakdown($wheelId);
    jsonResponse(['success' => true, 'categories' => $breakdown]);
}

// GET /api/analytics/heatmap - Get hourly activity
if ($path === '/api/analytics/heatmap' && $requestMethod === 'GET') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $wheelId = $_GET['wheel_id'] ?? 'default';
    $activity = $analytics->getHourlyActivity($wheelId);
    jsonResponse(['success' => true, 'activity' => $activity]);
}

// GET /api/analytics/funnel - Get conversion funnel
if ($path === '/api/analytics/funnel' && $requestMethod === 'GET') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $wheelId = $_GET['wheel_id'] ?? 'default';
    $dateFrom = $_GET['from'] ?? null;
    $dateTo = $_GET['to'] ?? null;

    $funnel = $analytics->getConversionFunnel($wheelId, $dateFrom, $dateTo);
    jsonResponse(['success' => true, 'funnel' => $funnel]);
}

// GET /api/analytics/export/csv - Export analytics to CSV
if ($path === '/api/analytics/export/csv' && $requestMethod === 'GET') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $wheelId = $_GET['wheel_id'] ?? 'default';
    $dateFrom = $_GET['from'] ?? null;
    $dateTo = $_GET['to'] ?? null;

    $data = $analytics->exportToCsv($wheelId, $dateFrom, $dateTo);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="analytics_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Spin ID', 'Date', 'Prize', 'Category', 'Winner', 'Session ID', 'User ID']);

    foreach ($data as $row) {
        fputcsv($output, [
            $row['spin_id'],
            $row['created_at'],
            $row['prize_name'],
            $row['category'],
            $row['is_winner'] ? 'Yes' : 'No',
            $row['session_id'],
            $row['user_id']
        ]);
    }

    fclose($output);
    exit;
}

// ==================== WHEELS ====================

// GET /api/wheels - Get all wheels
if ($path === '/api/wheels' && $requestMethod === 'GET') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';
    $wheels = $wheelManager->getAllWheels($activeOnly);
    jsonResponse(['success' => true, 'wheels' => $wheels]);
}

// POST /api/wheels - Create new wheel
if ($path === '/api/wheels' && $requestMethod === 'POST') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $wheelId = $wheelManager->createWheel($input);
    jsonResponse(['success' => true, 'wheel_id' => $wheelId]);
}

// GET /api/wheels/{slug} - Get wheel by slug
if (preg_match('#^/api/wheels/([^/]+)$#', $path, $matches) && $requestMethod === 'GET') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $slug = $matches[1];
    $wheel = $wheelManager->getWheelBySlug($slug);

    if ($wheel) {
        jsonResponse(['success' => true, 'wheel' => $wheel]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Wheel not found'], 404);
    }
}

// PUT /api/wheels/{id} - Update wheel
if (preg_match('#^/api/wheels/(\d+)$#', $path, $matches) && $requestMethod === 'PUT') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $wheelId = $matches[1];
    $input = json_decode(file_get_contents('php://input'), true);
    $result = $wheelManager->updateWheel($wheelId, $input);
    jsonResponse(['success' => $result]);
}

// DELETE /api/wheels/{id} - Delete wheel
if (preg_match('#^/api/wheels/(\d+)$#', $path, $matches) && $requestMethod === 'DELETE') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $wheelId = $matches[1];
    $result = $wheelManager->deleteWheel($wheelId);
    jsonResponse(['success' => $result]);
}

// POST /api/wheels/{id}/clone - Clone wheel
if (preg_match('#^/api/wheels/(\d+)/clone$#', $path, $matches) && $requestMethod === 'POST') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $wheelId = $matches[1];
    $input = json_decode(file_get_contents('php://input'), true);
    $newName = $input['name'] ?? 'Cloned Wheel';

    try {
        $newWheelId = $wheelManager->cloneWheel($wheelId, $newName);
        jsonResponse(['success' => true, 'wheel_id' => $newWheelId]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// GET /api/wheels/{id}/stats - Get wheel statistics
if (preg_match('#^/api/wheels/(\d+)/stats$#', $path, $matches) && $requestMethod === 'GET') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $wheelId = $matches[1];
    $stats = $wheelManager->getWheelStats($wheelId);
    jsonResponse(['success' => true, 'stats' => $stats]);
}

// ==================== WEBHOOKS ====================

// POST /api/webhooks - Register webhook
if ($path === '/api/webhooks' && $requestMethod === 'POST') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $webhookId = $webhookManager->registerWebhook(
        $input['url'],
        $input['events'],
        $input['secret'] ?? null
    );

    jsonResponse(['success' => true, 'webhook_id' => $webhookId]);
}

// GET /api/webhooks - List webhooks
if ($path === '/api/webhooks' && $requestMethod === 'GET') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $webhooks = $webhookManager->getActiveWebhooks();
    jsonResponse(['success' => true, 'webhooks' => $webhooks]);
}

// DELETE /api/webhooks/{id} - Delete webhook
if (preg_match('#^/api/webhooks/(\d+)$#', $path, $matches) && $requestMethod === 'DELETE') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $webhookId = $matches[1];
    $result = $webhookManager->deleteWebhook($webhookId);
    jsonResponse(['success' => $result]);
}

// ==================== IMPORT/EXPORT ====================

// POST /api/import/prizes/csv - Import prizes from CSV
if ($path === '/api/import/prizes/csv' && $requestMethod === 'POST') {
    if (!isset($_FILES['file'])) {
        jsonResponse(['success' => false, 'error' => 'No file uploaded'], 400);
    }

    $wheelId = $_POST['wheel_id'] ?? 'default';
    $result = $importExport->importPrizesFromCSV($_FILES['file']['tmp_name'], $wheelId);
    jsonResponse($result);
}

// POST /api/import/prizes/json - Import prizes from JSON
if ($path === '/api/import/prizes/json' && $requestMethod === 'POST') {
    $input = file_get_contents('php://input');
    $wheelId = $_GET['wheel_id'] ?? 'default';
    $result = $importExport->importPrizesFromJSON($input, $wheelId);
    jsonResponse($result);
}

// GET /api/export/prizes/csv - Export prizes to CSV
if ($path === '/api/export/prizes/csv' && $requestMethod === 'GET') {
    $wheelId = $_GET['wheel_id'] ?? 'default';
    $csv = $importExport->exportPrizesToCSV($wheelId);
    $importExport->downloadCSV($csv, 'prizes_' . date('Y-m-d') . '.csv');
}

// GET /api/export/prizes/json - Export prizes to JSON
if ($path === '/api/export/prizes/json' && $requestMethod === 'GET') {
    $wheelId = $_GET['wheel_id'] ?? 'default';
    $json = $importExport->exportPrizesToJSON($wheelId);

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="prizes_' . date('Y-m-d') . '.json"');
    echo $json;
    exit;
}

// POST /api/backup/create - Create full backup
if ($path === '/api/backup/create' && $requestMethod === 'POST') {
    $result = $importExport->createBackup();
    jsonResponse($result);
}

// GET /api/backup/list - List backups
if ($path === '/api/backup/list' && $requestMethod === 'GET') {
    $backups = $importExport->listBackups();
    jsonResponse(['success' => true, 'backups' => $backups]);
}

// GET /api/backup/download/{filename} - Download backup
if (preg_match('#^/api/backup/download/(.+)$#', $path, $matches) && $requestMethod === 'GET') {
    $filename = basename($matches[1]);
    $filePath = DATA_DIR . 'backups/' . $filename;

    if (file_exists($filePath)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile($filePath);
        exit;
    } else {
        jsonResponse(['success' => false, 'error' => 'Backup not found'], 404);
    }
}

// POST /api/backup/restore - Restore from backup
if ($path === '/api/backup/restore' && $requestMethod === 'POST') {
    if (!isset($_FILES['file'])) {
        jsonResponse(['success' => false, 'error' => 'No file uploaded'], 400);
    }

    $result = $importExport->restoreBackup($_FILES['file']['tmp_name']);
    jsonResponse($result);
}

// ==================== AUTHENTICATION ====================

// POST /api/auth/api-keys - Generate API key
if ($path === '/api/auth/api-keys' && $requestMethod === 'POST') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $apiKey = $authManager->generateApiKey(
        $input['name'],
        $input['permissions'] ?? [],
        $input['rate_limit'] ?? 100,
        $input['expires_at'] ?? null
    );

    jsonResponse(['success' => true, 'api_key' => $apiKey]);
}

// GET /api/auth/api-keys - List API keys
if ($path === '/api/auth/api-keys' && $requestMethod === 'GET') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $keys = $authManager->listApiKeys();
    jsonResponse(['success' => true, 'api_keys' => $keys]);
}

// DELETE /api/auth/api-keys/{id} - Revoke API key
if (preg_match('#^/api/auth/api-keys/(\d+)$#', $path, $matches) && $requestMethod === 'DELETE') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $keyId = $matches[1];
    $result = $authManager->revokeApiKey($keyId);
    jsonResponse(['success' => $result]);
}

// POST /api/auth/login - User login
if ($path === '/api/auth/login' && $requestMethod === 'POST') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $user = $authManager->authenticateUser($input['username'], $input['password']);

    if ($user) {
        $token = $authManager->generateJWT($user, JWT_SECRET ?? 'default-secret', 3600);
        jsonResponse(['success' => true, 'token' => $token, 'user' => $user]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Invalid credentials'], 401);
    }
}

// POST /api/auth/register - Create user account
if ($path === '/api/auth/register' && $requestMethod === 'POST') {
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Database not enabled'], 503);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $authManager->createUser(
        $input['username'],
        $input['email'],
        $input['password'],
        $input['role'] ?? 'user'
    );

    if ($userId) {
        jsonResponse(['success' => true, 'user_id' => $userId]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Failed to create user'], 500);
    }
}
