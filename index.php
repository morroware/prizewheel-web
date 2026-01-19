<?php
/**
 * Prize Wheel - Web Only Version
 * A standalone PHP web application for the prize wheel
 * No Raspberry Pi/GPIO dependencies
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session for state management
session_start();

// Calculate base path for subdirectory support
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = rtrim(dirname($scriptName), '/');
if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}
define('BASE_PATH', $basePath);

// Configuration
define('DATA_DIR', __DIR__ . '/data/');
define('STATIC_DIR', __DIR__ . '/static/');
define('CONFIG_FILE', DATA_DIR . 'config.json');
define('PRIZES_FILE', DATA_DIR . 'prizes.json');
define('HISTORY_FILE', DATA_DIR . 'history.json');
define('STATE_FILE', DATA_DIR . 'state.json');

// Ensure data directory exists
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Default configuration (no GPIO/Pi settings)
$DEFAULT_CONFIG = [
    'spin_duration_seconds' => 8,
    'cooldown_seconds' => 3,
    'volume' => 75,
    'system_sounds' => [
        'spin' => '/static/sounds/spin.mp3',
        'winner' => '/static/sounds/victory.mp3',
        'loser' => '/static/sounds/try-again.mp3'
    ],
    'modal_delay_ms' => 4500,
    'modal_auto_close_ms' => 6000,
    'winner_flash_duration_ms' => 4000,
    'display_settings' => [
        'enable_confetti' => true,
        'show_instructions' => false,
        'show_stats' => true,
        'animation_speed' => 'normal'
    ],
    'audio_settings' => [
        'enable_sound_effects' => true,
        'master_volume' => 75
    ]
];

// Default prizes
$DEFAULT_PRIZES = [
    [
        'id' => '1',
        'name' => 'GRAND PRIZE',
        'description' => 'A grand prize from the royal treasury!',
        'weight' => 0.1,
        'color' => '#00f531',
        'is_winner' => true,
        'sound_path' => '/static/sounds/winning_fanfare1.mp3',
        'enabled' => true
    ],
    [
        'id' => '2',
        'name' => 'Try Again',
        'description' => 'The quest continues! Fortune awaits your next spin.',
        'weight' => 15.0,
        'color' => '#607d8b',
        'is_winner' => false,
        'sound_path' => '/static/sounds/Brass-fail4.mp3',
        'enabled' => true
    ]
];

/**
 * Load JSON file with fallback to default
 */
function loadJsonFile($filename, $default = []) {
    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        $data = json_decode($content, true);
        if ($data !== null) {
            return $data;
        }
    }
    return $default;
}

/**
 * Save JSON file with atomic write
 */
function saveJsonFile($filename, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $tempFile = $filename . '.tmp';

    if (file_put_contents($tempFile, $json, LOCK_EX) !== false) {
        rename($tempFile, $filename);
        return true;
    }
    return false;
}

/**
 * Get configuration
 */
function getConfig() {
    global $DEFAULT_CONFIG;
    return loadJsonFile(CONFIG_FILE, $DEFAULT_CONFIG);
}

/**
 * Save configuration
 */
function saveConfig($config) {
    return saveJsonFile(CONFIG_FILE, $config);
}

/**
 * Get prizes
 */
function getPrizes() {
    global $DEFAULT_PRIZES;
    return loadJsonFile(PRIZES_FILE, $DEFAULT_PRIZES);
}

/**
 * Save prizes
 */
function savePrizes($prizes) {
    return saveJsonFile(PRIZES_FILE, $prizes);
}

/**
 * Get spin history
 */
function getHistory() {
    return loadJsonFile(HISTORY_FILE, []);
}

/**
 * Save spin to history
 */
function addToHistory($spinData) {
    $history = getHistory();
    array_unshift($history, $spinData);
    // Keep only last 1000 entries
    $history = array_slice($history, 0, 1000);
    saveJsonFile(HISTORY_FILE, $history);
    return $history;
}

/**
 * Get wheel state
 */
function getWheelState() {
    $default = [
        'is_spinning' => false,
        'last_spin_time' => 0,
        'current_winner' => null,
        'spin_id' => null
    ];
    return loadJsonFile(STATE_FILE, $default);
}

/**
 * Save wheel state
 */
function saveWheelState($state) {
    return saveJsonFile(STATE_FILE, $state);
}

/**
 * Select winner based on weighted random selection
 */
function selectWinner($prizes) {
    $enabledPrizes = array_filter($prizes, function($p) {
        return isset($p['enabled']) && $p['enabled'];
    });

    if (empty($enabledPrizes)) {
        return null;
    }

    $totalWeight = array_sum(array_column($enabledPrizes, 'weight'));
    if ($totalWeight <= 0) {
        return null;
    }

    $random = mt_rand() / mt_getrandmax() * $totalWeight;
    $cumulative = 0;

    foreach ($enabledPrizes as $prize) {
        $cumulative += $prize['weight'];
        if ($random <= $cumulative) {
            return $prize;
        }
    }

    // Fallback to last prize
    return end($enabledPrizes);
}

/**
 * Calculate statistics
 */
function calculateStats() {
    $history = getHistory();
    $prizes = getPrizes();

    $totalSpins = count($history);
    $totalWins = count(array_filter($history, function($h) {
        return isset($h['is_winner']) && $h['is_winner'];
    }));

    $winRate = $totalSpins > 0 ? round(($totalWins / $totalSpins) * 100, 1) : 0;
    $activePrizes = count(array_filter($prizes, function($p) {
        return isset($p['enabled']) && $p['enabled'];
    }));

    return [
        'total_spins' => $totalSpins,
        'total_wins' => $totalWins,
        'win_rate' => $winRate,
        'active_prizes' => $activePrizes,
        'connected_clients' => 1 // Static for polling-based version
    ];
}

/**
 * Router - Parse the request
 */
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string from URI
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove base path if running in subdirectory
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath !== '/' && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}
if (empty($path)) $path = '/';

// Set JSON content type for API responses
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Handle static files
if (strpos($path, '/static/') === 0) {
    $filePath = __DIR__ . $path;
    if (file_exists($filePath) && is_file($filePath)) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'm4a' => 'audio/mp4'
        ];
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
        readfile($filePath);
        exit;
    }
    http_response_code(404);
    exit;
}

// API Routes
if (strpos($path, '/api/') === 0) {

    // GET /api/prizes - Get all prizes
    if ($path === '/api/prizes' && $requestMethod === 'GET') {
        $prizes = getPrizes();
        jsonResponse(['success' => true, 'prizes' => $prizes]);
    }

    // POST /api/prizes - Create new prize
    if ($path === '/api/prizes' && $requestMethod === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $prizes = getPrizes();

        $newPrize = [
            'id' => uniqid(),
            'name' => $input['name'] ?? 'New Prize',
            'description' => $input['description'] ?? '',
            'weight' => floatval($input['weight'] ?? 1.0),
            'color' => $input['color'] ?? '#4CAF50',
            'is_winner' => $input['is_winner'] ?? true,
            'sound_path' => $input['sound_path'] ?? '',
            'enabled' => $input['enabled'] ?? true
        ];

        $prizes[] = $newPrize;
        savePrizes($prizes);

        jsonResponse(['success' => true, 'prize' => $newPrize]);
    }

    // PUT /api/prizes/{id} - Update prize
    if (preg_match('#^/api/prizes/([^/]+)$#', $path, $matches) && $requestMethod === 'PUT') {
        $prizeId = $matches[1];
        $input = json_decode(file_get_contents('php://input'), true);
        $prizes = getPrizes();

        $updated = false;
        foreach ($prizes as &$prize) {
            if ($prize['id'] === $prizeId) {
                $prize['name'] = $input['name'] ?? $prize['name'];
                $prize['description'] = $input['description'] ?? $prize['description'];
                $prize['weight'] = floatval($input['weight'] ?? $prize['weight']);
                $prize['color'] = $input['color'] ?? $prize['color'];
                $prize['is_winner'] = $input['is_winner'] ?? $prize['is_winner'];
                $prize['sound_path'] = $input['sound_path'] ?? $prize['sound_path'];
                $prize['enabled'] = $input['enabled'] ?? $prize['enabled'];
                $updated = true;
                break;
            }
        }

        if ($updated) {
            savePrizes($prizes);
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Prize not found'], 404);
        }
    }

    // DELETE /api/prizes/{id} - Delete prize
    if (preg_match('#^/api/prizes/([^/]+)$#', $path, $matches) && $requestMethod === 'DELETE') {
        $prizeId = $matches[1];
        $prizes = getPrizes();

        $prizes = array_filter($prizes, function($p) use ($prizeId) {
            return $p['id'] !== $prizeId;
        });
        $prizes = array_values($prizes);

        savePrizes($prizes);
        jsonResponse(['success' => true]);
    }

    // POST /api/spin - Trigger a spin
    if ($path === '/api/spin' && $requestMethod === 'POST') {
        $state = getWheelState();
        $config = getConfig();

        // Check if already spinning or in cooldown
        $now = time();
        $cooldown = $config['cooldown_seconds'] ?? 3;
        $spinDuration = $config['spin_duration_seconds'] ?? 8;

        if ($state['is_spinning']) {
            jsonResponse(['success' => false, 'error' => 'Wheel is already spinning', 'state' => 'spinning']);
        }

        if ($state['last_spin_time'] && ($now - $state['last_spin_time']) < ($spinDuration + $cooldown)) {
            $remaining = ($spinDuration + $cooldown) - ($now - $state['last_spin_time']);
            jsonResponse(['success' => false, 'error' => 'Please wait', 'cooldown_remaining' => $remaining, 'state' => 'cooldown']);
        }

        // Select winner
        $prizes = getPrizes();
        $winner = selectWinner($prizes);

        if (!$winner) {
            jsonResponse(['success' => false, 'error' => 'No enabled prizes available'], 400);
        }

        // Create spin ID
        $spinId = uniqid('spin_');

        // Update state
        $state['is_spinning'] = true;
        $state['last_spin_time'] = $now;
        $state['current_winner'] = $winner;
        $state['spin_id'] = $spinId;
        saveWheelState($state);

        // Record in history
        $historyEntry = [
            'spin_id' => $spinId,
            'prize_id' => $winner['id'],
            'prize_name' => $winner['name'],
            'is_winner' => $winner['is_winner'],
            'timestamp' => date('c')
        ];
        addToHistory($historyEntry);

        jsonResponse([
            'success' => true,
            'spin_id' => $spinId,
            'winner' => $winner,
            'prizes' => $prizes,
            'spin_duration' => $spinDuration * 1000,
            'cooldown_duration' => $cooldown * 1000
        ]);
    }

    // GET /api/spin/status - Get current spin status
    if ($path === '/api/spin/status' && $requestMethod === 'GET') {
        $state = getWheelState();
        $config = getConfig();

        $spinDuration = $config['spin_duration_seconds'] ?? 8;
        $cooldown = $config['cooldown_seconds'] ?? 3;
        $now = time();

        // Check if spin has completed
        if ($state['is_spinning'] && $state['last_spin_time']) {
            $elapsed = $now - $state['last_spin_time'];
            if ($elapsed >= $spinDuration) {
                $state['is_spinning'] = false;
                saveWheelState($state);
            }
        }

        // Calculate remaining cooldown
        $cooldownRemaining = 0;
        if ($state['last_spin_time']) {
            $totalWait = $spinDuration + $cooldown;
            $elapsed = $now - $state['last_spin_time'];
            if ($elapsed < $totalWait) {
                $cooldownRemaining = $totalWait - $elapsed;
            }
        }

        $status = 'ready';
        if ($state['is_spinning']) {
            $status = 'spinning';
        } elseif ($cooldownRemaining > 0) {
            $status = 'cooldown';
        }

        jsonResponse([
            'success' => true,
            'status' => $status,
            'is_spinning' => $state['is_spinning'],
            'cooldown_remaining' => $cooldownRemaining,
            'current_winner' => $state['current_winner'],
            'spin_id' => $state['spin_id']
        ]);
    }

    // POST /api/spin/complete - Mark spin as complete
    if ($path === '/api/spin/complete' && $requestMethod === 'POST') {
        $state = getWheelState();
        $state['is_spinning'] = false;
        saveWheelState($state);

        jsonResponse([
            'success' => true,
            'winner' => $state['current_winner']
        ]);
    }

    // GET /api/config - Get configuration
    if ($path === '/api/config' && $requestMethod === 'GET') {
        $config = getConfig();
        jsonResponse(['success' => true, 'config' => $config]);
    }

    // POST /api/config - Save configuration
    if ($path === '/api/config' && $requestMethod === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $config = getConfig();

        // Update only provided values
        foreach ($input as $key => $value) {
            $config[$key] = $value;
        }

        saveConfig($config);
        jsonResponse(['success' => true]);
    }

    // GET /api/dashboard_data - Get all dashboard data
    if ($path === '/api/dashboard_data' && $requestMethod === 'GET') {
        $prizes = getPrizes();
        $config = getConfig();
        $history = getHistory();
        $stats = calculateStats();

        jsonResponse([
            'success' => true,
            'prizes' => $prizes,
            'config' => $config,
            'recent_spins' => array_slice($history, 0, 20),
            'stats' => $stats
        ]);
    }

    // GET /api/sounds/list - List available sounds
    if ($path === '/api/sounds/list' && $requestMethod === 'GET') {
        $soundsDir = STATIC_DIR . 'sounds/';
        $sounds = [];

        if (is_dir($soundsDir)) {
            $files = scandir($soundsDir);
            foreach ($files as $file) {
                if (preg_match('/\.(mp3|wav|ogg|m4a)$/i', $file)) {
                    $sounds[] = BASE_PATH . '/static/sounds/' . $file;
                }
            }
        }

        jsonResponse(['success' => true, 'sounds' => $sounds]);
    }

    // POST /api/upload/sound - Upload a sound file
    if ($path === '/api/upload/sound' && $requestMethod === 'POST') {
        if (!isset($_FILES['file'])) {
            jsonResponse(['success' => false, 'error' => 'No file uploaded'], 400);
        }

        $file = $_FILES['file'];
        $allowedTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4'];

        if (!in_array($file['type'], $allowedTypes)) {
            jsonResponse(['success' => false, 'error' => 'Invalid file type'], 400);
        }

        if ($file['size'] > 16 * 1024 * 1024) {
            jsonResponse(['success' => false, 'error' => 'File too large (max 16MB)'], 400);
        }

        $soundsDir = STATIC_DIR . 'sounds/';
        if (!is_dir($soundsDir)) {
            mkdir($soundsDir, 0755, true);
        }

        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
        $destination = $soundsDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            jsonResponse(['success' => true, 'path' => BASE_PATH . '/static/sounds/' . $filename]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to save file'], 500);
        }
    }

    // DELETE /api/stats - Clear history
    if ($path === '/api/stats' && $requestMethod === 'DELETE') {
        saveJsonFile(HISTORY_FILE, []);
        jsonResponse(['success' => true]);
    }

    // GET /api/export/csv - Export history as CSV
    if ($path === '/api/export/csv' && $requestMethod === 'GET') {
        $history = getHistory();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="spin_history.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Spin ID', 'Prize Name', 'Is Winner', 'Timestamp']);

        foreach ($history as $entry) {
            fputcsv($output, [
                $entry['spin_id'] ?? '',
                $entry['prize_name'] ?? '',
                $entry['is_winner'] ? 'Yes' : 'No',
                $entry['timestamp'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    }

    // GET /api/odds/prizes - Get prizes for odds calculator
    if ($path === '/api/odds/prizes' && $requestMethod === 'GET') {
        $prizes = getPrizes();
        jsonResponse(['success' => true, 'prizes' => $prizes]);
    }

    // POST /api/odds/simulate - Run simulation
    if ($path === '/api/odds/simulate' && $requestMethod === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $simulations = min(intval($input['simulations'] ?? 1000), 10000);

        $prizes = getPrizes();
        $enabledPrizes = array_filter($prizes, function($p) {
            return isset($p['enabled']) && $p['enabled'];
        });

        $totalWeight = array_sum(array_column($enabledPrizes, 'weight'));

        // Initialize results
        $results = [];
        foreach ($enabledPrizes as $prize) {
            $results[$prize['id']] = [
                'name' => $prize['name'],
                'is_winner' => $prize['is_winner'],
                'count' => 0,
                'expected_percentage' => $totalWeight > 0 ? ($prize['weight'] / $totalWeight) * 100 : 0
            ];
        }

        // Run simulations
        $totalWinners = 0;
        for ($i = 0; $i < $simulations; $i++) {
            $winner = selectWinner($prizes);
            if ($winner) {
                $results[$winner['id']]['count']++;
                if ($winner['is_winner']) {
                    $totalWinners++;
                }
            }
        }

        // Calculate actual percentages
        $resultArray = [];
        foreach ($results as $id => $data) {
            $data['actual_percentage'] = ($data['count'] / $simulations) * 100;
            $resultArray[] = $data;
        }

        jsonResponse([
            'success' => true,
            'simulations' => $simulations,
            'total_winners' => $totalWinners,
            'win_rate' => ($totalWinners / $simulations) * 100,
            'results' => $resultArray
        ]);
    }

    // API endpoint not found
    jsonResponse(['success' => false, 'error' => 'Endpoint not found'], 404);
}

// Page Routes
$config = getConfig();
$prizes = getPrizes();

// Display page
if ($path === '/' || $path === '/display') {
    include __DIR__ . '/templates/display.php';
    exit;
}

// Dashboard page
if ($path === '/dashboard') {
    include __DIR__ . '/templates/dashboard.php';
    exit;
}

// Odds calculator page
if ($path === '/odds') {
    include __DIR__ . '/templates/odds_calculator.php';
    exit;
}

// 404 for unknown routes
http_response_code(404);
echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 - Page Not Found</h1><p><a href="' . BASE_PATH . '/">Go to Prize Wheel</a></p></body></html>';
