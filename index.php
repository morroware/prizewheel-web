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

// Configuration - use USER_DATA_DIR for writable paths in packaged Electron builds
$userDataDir = getenv('USER_DATA_DIR');
if ($userDataDir !== false && $userDataDir !== '') {
    define('DATA_DIR', rtrim($userDataDir, '/\\') . '/data/');
    define('STATIC_DIR', rtrim($userDataDir, '/\\') . '/static/');
    define('BUNDLED_STATIC_DIR', __DIR__ . '/static/');
} else {
    define('DATA_DIR', __DIR__ . '/data/');
    define('STATIC_DIR', __DIR__ . '/static/');
    define('BUNDLED_STATIC_DIR', __DIR__ . '/static/');
}
define('CONFIG_FILE', DATA_DIR . 'config.json');
define('PRIZES_FILE', DATA_DIR . 'prizes.json');
define('HISTORY_FILE', DATA_DIR . 'history.json');
define('STATE_FILE', DATA_DIR . 'state.json');
define('CUSTOMIZATION_FILE', DATA_DIR . 'customization.json');
define('PRESETS_DIR', DATA_DIR . 'presets/');

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
        'spin' => '/static/sounds/spin.wav',
        'winner' => '/static/sounds/victory.wav',
        'loser' => '/static/sounds/try-again.wav',
        'tick' => '/static/sounds/tick.wav'
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
        'sound_path' => '/static/sounds/victory.wav',
        'enabled' => true
    ],
    [
        'id' => '2',
        'name' => 'Try Again',
        'description' => 'The quest continues! Fortune awaits your next spin.',
        'weight' => 15.0,
        'color' => '#607d8b',
        'is_winner' => false,
        'sound_path' => '/static/sounds/try-again.wav',
        'enabled' => true
    ]
];

// Default customization
$DEFAULT_CUSTOMIZATION = [
    'meta' => [
        'version' => '1.0.0',
        'name' => 'Default Theme',
        'description' => 'Default prize wheel theme'
    ],
    'branding' => [
        'title' => 'Prize Wheel',
        'subtitle' => '',
        'logo_url' => '',
        'favicon_url' => '',
        'show_branding_badge' => true,
        'badge_text' => 'Prize Wheel'
    ],
    'theme' => [
        'preset' => 'royal',
        'colors' => [
            'primary' => '#FFD700',
            'secondary' => '#6B46C1',
            'accent' => '#FFA500',
            'background' => '#0a0a14',
            'background_secondary' => '#1a1a2e',
            'text_primary' => '#ffffff',
            'text_secondary' => 'rgba(255,255,255,0.7)',
            'success' => '#4caf50',
            'error' => '#f44336',
            'warning' => '#ff9800'
        ],
        'gradients' => [
            'background' => 'linear-gradient(135deg, #0a0a14 0%, #1a1a2e 35%, #16213e 70%, #0a0a14 100%)',
            'overlay_top_left' => 'radial-gradient(ellipse at top left, rgba(107,70,193,0.15) 0%, transparent 50%)',
            'overlay_top_right' => 'radial-gradient(ellipse at top right, rgba(30,58,138,0.15) 0%, transparent 50%)',
            'overlay_bottom' => 'radial-gradient(ellipse at bottom, rgba(255,215,0,0.08) 0%, transparent 40%)'
        ],
        'fonts' => [
            'heading' => "'Cinzel', serif",
            'body' => "'Montserrat', sans-serif",
            'google_fonts_url' => 'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;800&family=Montserrat:wght@400;600;800&display=swap'
        ]
    ],
    'wheel' => [
        'size' => ['max_size_px' => 900, 'size_vmin' => 85, 'mobile_size_vmin' => 92],
        'segments' => ['border_width' => 3, 'border_color' => 'rgba(0,0,0,0.4)', 'gradient_enabled' => true],
        'text' => ['font_family' => "'Cinzel', serif", 'font_weight' => '900', 'color' => '#ffffff'],
        'center' => ['icon' => '\u2654', 'icon_color' => '#FFD700'],
        'bezel' => ['enabled' => true, 'colors' => ['#FFD700', '#FFA500', '#CD7F32', '#FF8C00']],
        'studs' => ['enabled' => true, 'count' => 32, 'colors' => ['#FFD700', '#FFA500', '#CD7F32']],
        'pointer' => ['color' => '#FFD700', 'glow_color' => 'rgba(255,215,0,0.8)'],
        'animation' => ['float_enabled' => true]
    ],
    'effects' => [
        'confetti' => [
            'enabled' => true,
            'winner_colors' => ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#9370DB', '#FF6347', '#32CD32'],
            'loser_colors' => ['#C0C0C0', '#A8A8A8', '#D3D3D3', '#B8860B']
        ],
        'winner_flash' => ['enabled' => true, 'duration_ms' => 4000]
    ],
    'modal' => [
        'delay_ms' => 4500,
        'auto_close_ms' => 6000,
        'winner' => ['crest_icon' => '&#127942;', 'badge_text' => 'WINNER', 'title_text' => 'Royal Victory'],
        'loser' => ['crest_icon' => '&#128737;', 'badge_text' => 'TRY AGAIN', 'title_text' => 'Noble Effort']
    ],
    'sounds' => [
        'enabled' => true,
        'master_volume' => 75
    ],
    'status_indicator' => ['enabled' => true],
    'accessibility' => ['keyboard_controls' => true],
    'advanced' => ['custom_css' => '', 'custom_js' => '']
];

/**
 * Get customization settings
 */
function getCustomization() {
    global $DEFAULT_CUSTOMIZATION;
    $customization = loadJsonFile(CUSTOMIZATION_FILE, $DEFAULT_CUSTOMIZATION);
    return array_replace_recursive($DEFAULT_CUSTOMIZATION, $customization);
}

/**
 * Save customization settings
 */
function saveCustomization($customization) {
    return saveJsonFile(CUSTOMIZATION_FILE, $customization);
}

/**
 * Get available theme presets
 */
function getThemePresets() {
    $presetsFile = PRESETS_DIR . 'themes.json';
    if (file_exists($presetsFile)) {
        $data = json_decode(file_get_contents($presetsFile), true);
        return $data['presets'] ?? [];
    }
    return [];
}

/**
 * Deep merge arrays recursively
 */
function arrayMergeDeep($base, $overlay) {
    foreach ($overlay as $key => $value) {
        if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
            $base[$key] = arrayMergeDeep($base[$key], $value);
        } else {
            $base[$key] = $value;
        }
    }
    return $base;
}

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

// For PHP built-in server, let it handle static files directly if they exist
// This check runs BEFORE any path manipulation
if (php_sapi_name() === 'cli-server') {
    $staticFile = __DIR__ . $path;
    if (is_file($staticFile)) {
        // Return false to let PHP's built-in server handle the file
        // But NOT for /static/sounds/ when USER_DATA_DIR is set — those need routing
        $userDataDir = getenv('USER_DATA_DIR');
        if ($userDataDir && strpos($path, '/static/sounds/') === 0) {
            // Let index.php handle it so we serve from user data dir
        } else {
            return false;
        }
    }
}

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
    // First check user data dir (writable location for uploads)
    $filePath = STATIC_DIR . substr($path, strlen('/static/'));

    // Fall back to bundled static dir for default assets (CSS, JS, images, fonts)
    if ((!file_exists($filePath) || !is_file($filePath)) && defined('BUNDLED_STATIC_DIR')) {
        $bundledPath = BUNDLED_STATIC_DIR . substr($path, strlen('/static/'));
        if (file_exists($bundledPath) && is_file($bundledPath)) {
            $filePath = $bundledPath;
        }
    }

    // Debug logging
    error_log("Static file request - Path: $path, FilePath: $filePath, Exists: " . (file_exists($filePath) ? 'yes' : 'no'));

    if (file_exists($filePath) && is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'webp' => 'image/webp',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'm4a' => 'audio/mp4',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf'
        ];

        $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=86400');

        readfile($filePath);
        exit;
    }

    error_log("Static file NOT FOUND - Path: $path, Tried: $filePath, __DIR__: " . __DIR__);
    http_response_code(404);
    echo "File not found: $path";
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
        $config = getConfig();
        $cooldown = $config['cooldown_seconds'] ?? 3;
        $spinDuration = $config['spin_duration_seconds'] ?? 8;
        $now = time();

        // Use session-based cooldown instead of global state
        // This allows each browser/session to work independently
        $sessionLastSpin = $_SESSION['last_spin_time'] ?? 0;
        $totalCooldown = $spinDuration + $cooldown;

        if ($sessionLastSpin && ($now - $sessionLastSpin) < $totalCooldown) {
            $remaining = $totalCooldown - ($now - $sessionLastSpin);
            jsonResponse(['success' => false, 'error' => 'Please wait ' . $remaining . ' seconds', 'cooldown_remaining' => $remaining, 'state' => 'cooldown']);
        }

        // Select winner
        $prizes = getPrizes();
        $winner = selectWinner($prizes);

        if (!$winner) {
            jsonResponse(['success' => false, 'error' => 'No enabled prizes available'], 400);
        }

        // Create spin ID
        $spinId = uniqid('spin_');

        // Update session-based cooldown (not global state)
        $_SESSION['last_spin_time'] = $now;

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
        $config = getConfig();
        $spinDuration = $config['spin_duration_seconds'] ?? 8;
        $cooldown = $config['cooldown_seconds'] ?? 3;
        $now = time();

        // Use session-based state
        $sessionLastSpin = $_SESSION['last_spin_time'] ?? 0;
        $totalCooldown = $spinDuration + $cooldown;

        $cooldownRemaining = 0;
        $status = 'ready';

        if ($sessionLastSpin) {
            $elapsed = $now - $sessionLastSpin;
            if ($elapsed < $spinDuration) {
                $status = 'spinning';
            } elseif ($elapsed < $totalCooldown) {
                $status = 'cooldown';
                $cooldownRemaining = $totalCooldown - $elapsed;
            }
        }

        jsonResponse([
            'success' => true,
            'status' => $status,
            'is_spinning' => ($status === 'spinning'),
            'cooldown_remaining' => $cooldownRemaining
        ]);
    }

    // POST /api/spin/complete - Mark spin as complete (no-op now, kept for compatibility)
    if ($path === '/api/spin/complete' && $requestMethod === 'POST') {
        jsonResponse([
            'success' => true
        ]);
    }

    // POST /api/state/reset - Force reset wheel state (resets session cooldown)
    if ($path === '/api/state/reset' && $requestMethod === 'POST') {
        // Reset session cooldown
        $_SESSION['last_spin_time'] = 0;

        // Also reset global state file for backwards compatibility
        $state = [
            'is_spinning' => false,
            'last_spin_time' => 0,
            'current_winner' => null,
            'spin_id' => null
        ];
        saveWheelState($state);

        jsonResponse([
            'success' => true,
            'message' => 'Wheel state has been reset'
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
        $allowedTypes = ['audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/mp4'];

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

    // POST /api/sounds/delete - Delete a sound file
    if ($path === '/api/sounds/delete' && $requestMethod === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $path = $input['path'] ?? '';

        if (empty($path)) {
            jsonResponse(['success' => false, 'error' => 'No path provided'], 400);
        }

        // Extract filename from path and validate
        $filename = basename($path);

        // Prevent deletion of default sounds
        $defaultSounds = ['spin.wav', 'victory.wav', 'try-again.wav', 'tick.wav'];
        if (in_array($filename, $defaultSounds)) {
            jsonResponse(['success' => false, 'error' => 'Cannot delete default system sounds'], 400);
        }

        // Only allow deleting from sounds directory
        $soundsDir = STATIC_DIR . 'sounds/';
        $fullPath = $soundsDir . $filename;

        // Security check: ensure path is within sounds directory
        $realSoundsDir = realpath($soundsDir);
        $realFullPath = realpath($fullPath);

        if ($realFullPath === false || strpos($realFullPath, $realSoundsDir) !== 0) {
            jsonResponse(['success' => false, 'error' => 'Invalid path'], 400);
        }

        if (!file_exists($fullPath)) {
            jsonResponse(['success' => false, 'error' => 'File not found'], 404);
        }

        if (unlink($fullPath)) {
            jsonResponse(['success' => true, 'message' => 'Sound deleted successfully']);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to delete file'], 500);
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

    // GET /api/customization - Get customization settings
    if ($path === '/api/customization' && $requestMethod === 'GET') {
        $customization = getCustomization();
        jsonResponse(['success' => true, 'customization' => $customization]);
    }

    // POST /api/customization - Save customization settings
    if ($path === '/api/customization' && $requestMethod === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            jsonResponse(['success' => false, 'error' => 'Invalid JSON data'], 400);
        }

        $customization = getCustomization();
        $customization = arrayMergeDeep($customization, $input);

        if (saveCustomization($customization)) {
            jsonResponse(['success' => true, 'customization' => $customization]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to save customization'], 500);
        }
    }

    // GET /api/presets/themes - Get available theme presets
    if ($path === '/api/presets/themes' && $requestMethod === 'GET') {
        $presets = getThemePresets();
        jsonResponse(['success' => true, 'presets' => $presets]);
    }

    // POST /api/customization/apply-preset - Apply a theme preset
    if ($path === '/api/customization/apply-preset' && $requestMethod === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $presetId = $input['preset_id'] ?? null;

        if (!$presetId) {
            jsonResponse(['success' => false, 'error' => 'No preset_id provided'], 400);
        }

        $presets = getThemePresets();
        $selectedPreset = null;

        foreach ($presets as $preset) {
            if ($preset['id'] === $presetId) {
                $selectedPreset = $preset;
                break;
            }
        }

        if (!$selectedPreset) {
            jsonResponse(['success' => false, 'error' => 'Preset not found'], 404);
        }

        $customization = getCustomization();

        // Apply preset theme settings
        if (isset($selectedPreset['theme'])) {
            $customization['theme'] = arrayMergeDeep($customization['theme'], $selectedPreset['theme']);
        }
        if (isset($selectedPreset['wheel'])) {
            $customization['wheel'] = arrayMergeDeep($customization['wheel'], $selectedPreset['wheel']);
        }
        if (isset($selectedPreset['effects'])) {
            $customization['effects'] = arrayMergeDeep($customization['effects'], $selectedPreset['effects']);
        }
        if (isset($selectedPreset['modal'])) {
            $customization['modal'] = arrayMergeDeep($customization['modal'], $selectedPreset['modal']);
        }

        $customization['theme']['preset'] = $presetId;
        $customization['meta']['name'] = $selectedPreset['name'];
        $customization['meta']['description'] = $selectedPreset['description'];

        if (saveCustomization($customization)) {
            jsonResponse(['success' => true, 'customization' => $customization, 'preset' => $selectedPreset]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to apply preset'], 500);
        }
    }

    // POST /api/customization/reset - Reset to defaults
    if ($path === '/api/customization/reset' && $requestMethod === 'POST') {
        global $DEFAULT_CUSTOMIZATION;
        if (saveCustomization($DEFAULT_CUSTOMIZATION)) {
            jsonResponse(['success' => true, 'customization' => $DEFAULT_CUSTOMIZATION]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to reset customization'], 500);
        }
    }

    // POST /api/customization/export - Export customization as JSON
    if ($path === '/api/customization/export' && $requestMethod === 'GET') {
        $customization = getCustomization();
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="wheel-customization.json"');
        echo json_encode($customization, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // POST /api/customization/import - Import customization from JSON
    if ($path === '/api/customization/import' && $requestMethod === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !is_array($input)) {
            jsonResponse(['success' => false, 'error' => 'Invalid customization data'], 400);
        }

        // Validate basic structure
        if (!isset($input['meta']) || !isset($input['theme'])) {
            jsonResponse(['success' => false, 'error' => 'Invalid customization format - missing required fields'], 400);
        }

        if (saveCustomization($input)) {
            jsonResponse(['success' => true, 'customization' => $input]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to import customization'], 500);
        }
    }

    // POST /api/upload/image - Upload an image (logo, background, etc.)
    if ($path === '/api/upload/image' && $requestMethod === 'POST') {
        if (!isset($_FILES['file'])) {
            jsonResponse(['success' => false, 'error' => 'No file uploaded'], 400);
        }

        $file = $_FILES['file'];
        $allowedTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp'];

        if (!in_array($file['type'], $allowedTypes)) {
            jsonResponse(['success' => false, 'error' => 'Invalid file type. Allowed: PNG, JPEG, GIF, SVG, WebP'], 400);
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            jsonResponse(['success' => false, 'error' => 'File too large (max 5MB)'], 400);
        }

        $imagesDir = STATIC_DIR . 'images/';
        if (!is_dir($imagesDir)) {
            mkdir($imagesDir, 0755, true);
        }

        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
        $destination = $imagesDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            jsonResponse(['success' => true, 'path' => BASE_PATH . '/static/images/' . $filename]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to save file'], 500);
        }
    }

    // GET /api/images/list - List available images
    if ($path === '/api/images/list' && $requestMethod === 'GET') {
        $imagesDir = STATIC_DIR . 'images/';
        $images = [];

        if (is_dir($imagesDir)) {
            $files = scandir($imagesDir);
            foreach ($files as $file) {
                if (preg_match('/\.(png|jpg|jpeg|gif|svg|webp)$/i', $file)) {
                    $images[] = BASE_PATH . '/static/images/' . $file;
                }
            }
        }

        jsonResponse(['success' => true, 'images' => $images]);
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
