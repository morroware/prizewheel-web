<?php
/**
 * Import/Export Manager - Handle CSV, JSON, Excel imports and backups
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PrizeManager.php';

class ImportExport {
    private $db;
    private $prizeManager;

    public function __construct($db = null) {
        $this->db = $db;
        $this->prizeManager = new PrizeManager($db);
    }

    /**
     * Import prizes from CSV
     */
    public function importPrizesFromCSV($filePath, $wheelId = 'default') {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found'];
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);

        $imported = 0;
        $errors = [];

        while (($row = fgetcsv($file)) !== false) {
            try {
                $prizeData = array_combine($headers, $row);

                // Map CSV fields
                $prize = [
                    'wheel_id' => $wheelId,
                    'name' => $prizeData['name'] ?? $prizeData['Name'] ?? 'Unnamed Prize',
                    'description' => $prizeData['description'] ?? $prizeData['Description'] ?? '',
                    'category' => $prizeData['category'] ?? $prizeData['Category'] ?? null,
                    'weight' => floatval($prizeData['weight'] ?? $prizeData['Weight'] ?? 1.0),
                    'color' => $prizeData['color'] ?? $prizeData['Color'] ?? '#4CAF50',
                    'is_winner' => $this->parseBool($prizeData['is_winner'] ?? $prizeData['Is Winner'] ?? true),
                    'sound_path' => $prizeData['sound_path'] ?? $prizeData['Sound'] ?? '',
                    'image_url' => $prizeData['image_url'] ?? $prizeData['Image'] ?? '',
                    'enabled' => $this->parseBool($prizeData['enabled'] ?? $prizeData['Enabled'] ?? true),
                    'stock_enabled' => $this->parseBool($prizeData['stock_enabled'] ?? false),
                    'stock_total' => intval($prizeData['stock_total'] ?? $prizeData['Stock'] ?? 0),
                    'stock_remaining' => intval($prizeData['stock_remaining'] ?? $prizeData['Stock'] ?? 0)
                ];

                $this->prizeManager->createPrize($prize);
                $imported++;
            } catch (Exception $e) {
                $errors[] = [
                    'row' => $row,
                    'error' => $e->getMessage()
                ];
            }
        }

        fclose($file);

        return [
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        ];
    }

    /**
     * Export prizes to CSV
     */
    public function exportPrizesToCSV($wheelId = 'default') {
        $prizes = $this->prizeManager->getPrizes($wheelId, ['ignore_dates' => true]);

        $headers = [
            'id', 'name', 'description', 'category', 'weight', 'color',
            'is_winner', 'sound_path', 'image_url', 'enabled',
            'stock_enabled', 'stock_total', 'stock_remaining',
            'start_date', 'end_date', 'created_at'
        ];

        $csv = [];
        $csv[] = $headers;

        foreach ($prizes as $prize) {
            $row = [];
            foreach ($headers as $header) {
                $row[] = $prize[$header] ?? '';
            }
            $csv[] = $row;
        }

        return $csv;
    }

    /**
     * Export prizes to JSON
     */
    public function exportPrizesToJSON($wheelId = 'default') {
        $prizes = $this->prizeManager->getPrizes($wheelId, ['ignore_dates' => true]);
        return json_encode($prizes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Import prizes from JSON
     */
    public function importPrizesFromJSON($json, $wheelId = 'default') {
        $prizes = json_decode($json, true);

        if (!is_array($prizes)) {
            return ['success' => false, 'error' => 'Invalid JSON format'];
        }

        $imported = 0;
        $errors = [];

        foreach ($prizes as $prizeData) {
            try {
                $prizeData['wheel_id'] = $wheelId;
                unset($prizeData['id']); // Remove old ID
                $this->prizeManager->createPrize($prizeData);
                $imported++;
            } catch (Exception $e) {
                $errors[] = [
                    'prize' => $prizeData['name'] ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        ];
    }

    /**
     * Create full backup (database + files)
     */
    public function createBackup() {
        $backupDir = DATA_DIR . 'backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . 'backup_' . $timestamp . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE) !== true) {
            return ['success' => false, 'error' => 'Could not create backup file'];
        }

        // Add database file
        if ($this->db && file_exists(DATA_DIR . 'prizewheel.db')) {
            $zip->addFile(DATA_DIR . 'prizewheel.db', 'prizewheel.db');
        }

        // Add JSON files
        $jsonFiles = ['prizes.json', 'config.json', 'customization.json', 'history.json', 'state.json'];
        foreach ($jsonFiles as $file) {
            if (file_exists(DATA_DIR . $file)) {
                $zip->addFile(DATA_DIR . $file, $file);
            }
        }

        // Add presets
        if (is_dir(DATA_DIR . 'presets/')) {
            $this->addDirectoryToZip($zip, DATA_DIR . 'presets/', 'presets/');
        }

        $zip->close();

        return [
            'success' => true,
            'file' => $backupFile,
            'filename' => basename($backupFile),
            'size' => filesize($backupFile)
        ];
    }

    /**
     * Restore from backup
     */
    public function restoreBackup($backupFile) {
        if (!file_exists($backupFile)) {
            return ['success' => false, 'error' => 'Backup file not found'];
        }

        $zip = new ZipArchive();
        if ($zip->open($backupFile) !== true) {
            return ['success' => false, 'error' => 'Could not open backup file'];
        }

        // Extract to data directory
        $zip->extractTo(DATA_DIR);
        $zip->close();

        return ['success' => true, 'message' => 'Backup restored successfully'];
    }

    /**
     * List available backups
     */
    public function listBackups() {
        $backupDir = DATA_DIR . 'backups/';
        if (!is_dir($backupDir)) {
            return [];
        }

        $backups = [];
        $files = scandir($backupDir);

        foreach ($files as $file) {
            if (preg_match('/^backup_.*\.zip$/', $file)) {
                $filePath = $backupDir . $file;
                $backups[] = [
                    'filename' => $file,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'created' => filemtime($filePath),
                    'created_formatted' => date('Y-m-d H:i:s', filemtime($filePath))
                ];
            }
        }

        // Sort by date descending
        usort($backups, function($a, $b) {
            return $b['created'] - $a['created'];
        });

        return $backups;
    }

    /**
     * Export configuration as theme
     */
    public function exportTheme($name, $description) {
        $customization = getCustomization();

        $theme = [
            'id' => uniqid('theme_'),
            'name' => $name,
            'description' => $description,
            'version' => '1.0.0',
            'theme' => $customization['theme'] ?? [],
            'wheel' => $customization['wheel'] ?? [],
            'effects' => $customization['effects'] ?? [],
            'modal' => $customization['modal'] ?? [],
            'created_at' => date('c')
        ];

        return json_encode($theme, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Import theme
     */
    public function importTheme($themeJson) {
        $theme = json_decode($themeJson, true);

        if (!$theme || !isset($theme['theme'])) {
            return ['success' => false, 'error' => 'Invalid theme format'];
        }

        $customization = getCustomization();

        // Merge theme data
        if (isset($theme['theme'])) {
            $customization['theme'] = array_replace_recursive($customization['theme'], $theme['theme']);
        }

        if (isset($theme['wheel'])) {
            $customization['wheel'] = array_replace_recursive($customization['wheel'], $theme['wheel']);
        }

        if (isset($theme['effects'])) {
            $customization['effects'] = array_replace_recursive($customization['effects'], $theme['effects']);
        }

        if (isset($theme['modal'])) {
            $customization['modal'] = array_replace_recursive($customization['modal'], $theme['modal']);
        }

        $customization['meta']['name'] = $theme['name'];
        $customization['meta']['description'] = $theme['description'] ?? '';

        if (saveCustomization($customization)) {
            return ['success' => true, 'customization' => $customization];
        }

        return ['success' => false, 'error' => 'Failed to save theme'];
    }

    /**
     * Helper: Parse boolean values from CSV
     */
    private function parseBool($value) {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim($value));
        return in_array($value, ['1', 'true', 'yes', 'y', 'on']);
    }

    /**
     * Helper: Add directory recursively to zip
     */
    private function addDirectoryToZip($zip, $sourceDir, $zipPath = '') {
        $files = scandir($sourceDir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $sourceDir . $file;
            $zipFilePath = $zipPath . $file;

            if (is_dir($filePath)) {
                $zip->addEmptyDir($zipFilePath);
                $this->addDirectoryToZip($zip, $filePath . '/', $zipFilePath . '/');
            } else {
                $zip->addFile($filePath, $zipFilePath);
            }
        }
    }

    /**
     * Generate CSV download response
     */
    public function downloadCSV($data, $filename = 'export.csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
