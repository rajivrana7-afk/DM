<?php
/**
 * HACKER CONTENT REMOVAL SCRIPT
 * ==============================
 * Removes Japanese SEO spam, webshells, and injected malicious code.
 *
 * Usage:
 *   php scripts/remove_hacker_content.php --dry-run   (preview only, no changes)
 *   php scripts/remove_hacker_content.php --fix        (apply all fixes)
 *   php scripts/remove_hacker_content.php --db-clean   (also clean database)
 *
 * ALWAYS run with --dry-run first!
 */

define('BASE_PATH', dirname(__DIR__));
define('LOG_FILE',  BASE_PATH . '/storage/logs/removal_' . date('Y-m-d_H-i-s') . '.log');

$dryRun  = in_array('--dry-run', $argv, true);
$fix     = in_array('--fix', $argv, true);
$dbClean = in_array('--db-clean', $argv, true);

if (!$dryRun && !$fix) {
    echo "Usage:\n";
    echo "  php remove_hacker_content.php --dry-run    Preview changes only\n";
    echo "  php remove_hacker_content.php --fix        Apply all file fixes\n";
    echo "  php remove_hacker_content.php --fix --db-clean  Fix files + clean DB\n\n";
    exit(1);
}

$log     = [];
$removed = 0;
$cleaned = 0;
$errors  = 0;

// ---------------------------------------------------------------
// Logging helper
// ---------------------------------------------------------------
function logMsg(string $level, string $msg): void
{
    global $log;
    $line = "[" . date('H:i:s') . "] [$level] $msg";
    $log[] = $line;
    echo $line . "\n";
}

// ---------------------------------------------------------------
// 1. WEBSHELL PATTERNS — files that match these are deleted
// ---------------------------------------------------------------
$webshellSignatures = [
    '/c99shell|r57shell|b374k|wso\.php|alfa\.php|indoxploit|FilesMan/i',
    '/eval\s*\(\s*base64_decode\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i',
    '/assert\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)\s*\[/i',
    '/\$_\w+\s*\(\s*\$_\w+\s*\[/i',   // $_POST($_GET['x']) pattern
    '/preg_replace\s*\(.+\/e[^\w]/i',
    '/system\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i',
    '/exec\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i',
    '/passthru\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i',
    '/shell_exec\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i',
    '/base64_decode\s*\([\'"][A-Za-z0-9+\/]{100,}[=]*[\'"]\)/i',
    '/\\\\x[0-9a-f]{2}\\\\x[0-9a-f]{2}\\\\x[0-9a-f]{2}\\\\x[0-9a-f]{2}/i',  // hex obfuscation
];

// ---------------------------------------------------------------
// 2. INJECTION PATTERNS — removed/cleaned from file content
// ---------------------------------------------------------------
$injectionPatterns = [
    // Japanese spam link blocks hidden in PHP/HTML
    '/<a[^>]+href=["\'][^"\']*["\'][^>]*>[\x{3040}-\x{9FFF}]+<\/a>/su',
    // Hidden divs with Japanese text
    '/<div[^>]+style=["\'][^"\']*display\s*:\s*none[^"\']*["\'][^>]*>.*?<\/div>/su',
    // Invisible spam iframes
    '/<iframe[^>]+(?:width\s*=\s*["\']0["\']|height\s*=\s*["\']0["\']|visibility\s*:\s*hidden)[^>]*>.*?<\/iframe>/su',
    // Injected base64 eval blocks
    '/\/\*[a-z0-9]{4,}\*\/\s*eval\s*\([^;]+\);/i',
    // Japanese keyword meta injections
    '/<meta[^>]+content=["\'][^"\']*[\x{3040}-\x{9FFF}]+[^"\']*["\'][^>]*>/su',
    // Null-byte injections
    '/\x00+/',
    // Common obfuscation wrappers
    '/\\$[a-zA-Z_]\w*\s*=\s*["\']\\\\[0-9]+[^"\']*["\']\s*;/',
];

// ---------------------------------------------------------------
// 3. PHP files that MUST NOT exist in upload paths
// ---------------------------------------------------------------
function isUnsafeUploadFile(string $path): bool
{
    $uploadDirs = ['/uploads/', '/storage/app/', '/public/storage/'];
    foreach ($uploadDirs as $dir) {
        if (str_contains($path, $dir)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            return in_array($ext, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'pht', 'phar', 'shtml']);
        }
    }
    return false;
}

// ---------------------------------------------------------------
// 4. Scan and process files
// ---------------------------------------------------------------
function processFiles(
    string $dir,
    array $webshellSigs,
    array $injectionPatterns,
    bool $dryRun,
    int &$removed,
    int &$cleaned,
    int &$errors
): void {
    if (!is_dir($dir)) return;

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iter as $file) {
        if (!$file->isFile()) continue;

        $path = $file->getRealPath();
        $ext  = strtolower($file->getExtension());

        // --- Delete PHP files in upload directories ---
        if (isUnsafeUploadFile($path)) {
            logMsg('CRITICAL', "WEBSHELL IN UPLOADS: $path");
            if (!$dryRun) {
                if (unlink($path)) {
                    logMsg('REMOVED', "Deleted: $path");
                    $removed++;
                } else {
                    logMsg('ERROR', "Cannot delete: $path");
                    $errors++;
                }
            } else {
                logMsg('DRY-RUN', "Would delete: $path");
            }
            continue;
        }

        if (!in_array($ext, ['php', 'phtml', 'html', 'htm', 'js', 'htaccess', 'blade'])) {
            continue;
        }

        $content = @file_get_contents($path);
        if ($content === false) continue;

        // --- Check for webshell signatures → delete entire file ---
        $isWebshell = false;
        foreach ($webshellSigs as $pattern) {
            if (preg_match($pattern, $content)) {
                logMsg('CRITICAL', "WEBSHELL DETECTED: $path  (pattern: $pattern)");
                $isWebshell = true;
                break;
            }
        }

        if ($isWebshell) {
            if (!$dryRun) {
                // Quarantine instead of outright delete (safer)
                $quarantine = BASE_PATH . '/storage/quarantine/' . md5($path) . '_' . basename($path);
                @mkdir(dirname($quarantine), 0700, true);
                if (rename($path, $quarantine)) {
                    logMsg('QUARANTINED', "Moved to quarantine: $path → $quarantine");
                    $removed++;
                } else {
                    logMsg('ERROR', "Cannot quarantine: $path");
                    $errors++;
                }
            } else {
                logMsg('DRY-RUN', "Would quarantine: $path");
            }
            continue;
        }

        // --- Remove injection patterns from file content ---
        $newContent = $content;
        $changed    = false;

        foreach ($injectionPatterns as $pattern) {
            $result = @preg_replace($pattern, '', $newContent);
            if ($result !== null && $result !== $newContent) {
                $bytesBefore = strlen($newContent);
                $newContent  = $result;
                $bytesAfter  = strlen($newContent);
                logMsg('INJECTION', "Cleaned $path  (removed " . ($bytesBefore - $bytesAfter) . " bytes, pattern: $pattern)");
                $changed = true;
            }
        }

        if ($changed) {
            if (!$dryRun) {
                // Backup original first
                $backup = BASE_PATH . '/storage/backups/' . date('Ymd_His') . '_' . md5($path) . '_' . basename($path);
                @mkdir(dirname($backup), 0700, true);
                copy($path, $backup);

                if (file_put_contents($path, $newContent) !== false) {
                    logMsg('CLEANED', "Fixed: $path  (backup: $backup)");
                    $cleaned++;
                } else {
                    logMsg('ERROR', "Cannot write: $path");
                    $errors++;
                }
            } else {
                logMsg('DRY-RUN', "Would clean: $path");
                $cleaned++;
            }
        }
    }
}

// ---------------------------------------------------------------
// 5. Database cleaner
// ---------------------------------------------------------------
function cleanDatabase(bool $dryRun): void
{
    // Load Laravel .env manually
    $envFile = BASE_PATH . '/.env';
    if (!file_exists($envFile)) {
        logMsg('ERROR', '.env file not found — skipping database clean');
        return;
    }

    $env = [];
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $val] = array_pad(explode('=', $line, 2), 2, '');
        $env[trim($key)] = trim($val, "'\" \t");
    }

    $required = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];
    foreach ($required as $key) {
        if (empty($env[$key])) {
            logMsg('ERROR', "Missing $key in .env — skipping DB clean");
            return;
        }
    }

    try {
        $pdo = new PDO(
            "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
            $env['DB_USERNAME'],
            $env['DB_PASSWORD'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        logMsg('ERROR', 'DB connection failed: ' . $e->getMessage());
        return;
    }

    // Japanese/CJK Unicode range pattern for MySQL
    $japaneseMysqlRegex = '[ぁ-ん]|[ァ-ン]|[一-龯]|[々〇〻]';

    // Tables and columns to check
    $targets = [
        ['table' => 'pages',              'col_content' => 'content',     'col_id' => 'id', 'col_label' => 'title'],
        ['table' => 'page_section_rows',  'col_content' => 'content',     'col_id' => 'id', 'col_label' => 'title'],
        ['table' => 'settings',           'col_content' => 'value',       'col_id' => 'id', 'col_label' => 'key'],
        ['table' => 'seo_meta',           'col_content' => 'meta_value',  'col_id' => 'id', 'col_label' => 'meta_key'],
    ];

    foreach ($targets as $target) {
        // Check table exists
        $check = $pdo->query("SHOW TABLES LIKE '{$target['table']}'")->fetchAll();
        if (empty($check)) {
            logMsg('INFO', "Table `{$target['table']}` not found — skipping");
            continue;
        }

        $sql = "SELECT {$target['col_id']}, {$target['col_label']}, {$target['col_content']}
                FROM {$target['table']}
                WHERE {$target['col_content']} REGEXP :pattern";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pattern' => $japaneseMysqlRegex]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            logMsg('OK', "No Japanese content in `{$target['table']}`");
            continue;
        }

        logMsg('FOUND', count($rows) . " rows with Japanese content in `{$target['table']}`");

        foreach ($rows as $row) {
            $id      = $row[$target['col_id']];
            $label   = $row[$target['col_label']];
            $content = $row[$target['col_content']];

            // Strip Japanese characters and surrounding HTML tags
            $cleaned = preg_replace(
                [
                    '/<a[^>]*>[\x{3040}-\x{9FFF}\s]+<\/a>/su',   // Japanese anchor tags
                    '/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]+/su', // Raw CJK chars
                ],
                '',
                $content
            );

            $cleaned = trim(preg_replace('/\s{3,}/', ' ', $cleaned)); // Collapse whitespace

            logMsg('DB-FIX', "Table={$target['table']} ID=$id Label=\"$label\"");

            if (!$dryRun) {
                $upd = $pdo->prepare(
                    "UPDATE {$target['table']} SET {$target['col_content']} = :content WHERE {$target['col_id']} = :id"
                );
                $upd->execute([':content' => $cleaned, ':id' => $id]);
                logMsg('DB-CLEANED', "Row ID=$id updated in `{$target['table']}`");
            } else {
                logMsg('DRY-RUN', "Would clean row ID=$id in `{$target['table']}`");
            }
        }
    }
}

// ---------------------------------------------------------------
// Run everything
// ---------------------------------------------------------------
$mode = $dryRun ? 'DRY-RUN (no changes)' : 'LIVE FIX';
logMsg('START', "===== Hacker Content Removal — Mode: $mode =====");

$scanDirs = [
    BASE_PATH . '/app',
    BASE_PATH . '/resources',
    BASE_PATH . '/public',
    BASE_PATH . '/routes',
    BASE_PATH . '/storage/app/public',
];

foreach ($scanDirs as $dir) {
    logMsg('SCAN', "Directory: $dir");
    processFiles($dir, $webshellSignatures, $injectionPatterns, $dryRun, $removed, $cleaned, $errors);
}

if ($dbClean) {
    logMsg('DB', '===== Database Cleaning =====');
    cleanDatabase($dryRun);
}

// Save log
@mkdir(dirname(LOG_FILE), 0755, true);
file_put_contents(LOG_FILE, implode("\n", $log));

logMsg('DONE', "Files quarantined/deleted: $removed | Files cleaned: $cleaned | Errors: $errors");
logMsg('LOG',  "Full log saved to: " . LOG_FILE);

if ($dryRun) {
    echo "\n*** DRY-RUN complete. Run with --fix to apply changes. ***\n";
}
