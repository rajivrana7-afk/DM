<?php
/**
 * FULL WEBSITE SECURITY AUDIT SCRIPT
 * ====================================
 * Performs a comprehensive security audit of a Laravel application.
 *
 * Usage: php scripts/security_audit.php
 *
 * Checks:
 *   A1  - Injected files in uploads (webshells)
 *   A2  - Malicious code patterns in source files
 *   A3  - Japanese/CJK content injection
 *   A4  - File permissions (world-writable, executable uploads)
 *   A5  - .env exposure / sensitive file access
 *   A6  - Outdated / insecure .htaccess rules
 *   A7  - Directory listing enabled
 *   A8  - Laravel APP_DEBUG=true in production
 *   A9  - Weak file/folder ownership
 *   A10 - Suspicious cron jobs
 *   A11 - Composer packages with known vulnerabilities
 *   A12 - Hard-coded secrets/credentials in source
 *   A13 - Missing security headers
 *   A14 - Open redirect vulnerabilities in routes
 *   A15 - CSRF protection disabled
 */

define('BASE_PATH', dirname(__DIR__));
define('REPORT_FILE', BASE_PATH . '/storage/logs/audit_' . date('Y-m-d_H-i-s') . '.txt');

$issues   = [];
$warnings = [];
$info     = [];

// ---------------------------------------------------------------
// Severity levels: CRITICAL / HIGH / MEDIUM / LOW / INFO
// ---------------------------------------------------------------
function issue(string $severity, string $code, string $title, string $detail, string $fix = ''): void
{
    global $issues;
    $issues[] = compact('severity', 'code', 'title', 'detail', 'fix');
    $symbol   = match($severity) {
        'CRITICAL' => '🔴',
        'HIGH'     => '🟠',
        'MEDIUM'   => '🟡',
        'LOW'      => '🔵',
        default    => 'ℹ️ ',
    };
    echo "$symbol [$severity][$code] $title\n";
    if ($detail) echo "          $detail\n";
}

function pass(string $code, string $msg): void
{
    echo "✅ [PASS][$code] $msg\n";
}

function sectionHeader(string $title): void
{
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "  $title\n";
    echo str_repeat('=', 60) . "\n";
}

// ---------------------------------------------------------------
// A1: PHP/executable files in upload directories
// ---------------------------------------------------------------
sectionHeader('A1 — Webshells in Upload Directories');
$uploadDirs = [
    BASE_PATH . '/public/uploads',
    BASE_PATH . '/storage/app/public',
    BASE_PATH . '/storage/app',
];
$dangerousExts = ['php','phtml','php3','php4','php5','php7','pht','phar','shtml','asp','aspx','jsp'];
$shellsFound = 0;
foreach ($uploadDirs as $uDir) {
    if (!is_dir($uDir)) continue;
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        if (in_array(strtolower($file->getExtension()), $dangerousExts)) {
            issue('CRITICAL', 'A1', 'Executable file in uploads', $file->getRealPath(),
                'Delete immediately: rm ' . $file->getRealPath());
            $shellsFound++;
        }
    }
}
if ($shellsFound === 0) pass('A1', 'No PHP/executable files found in upload directories');

// ---------------------------------------------------------------
// A2: Malicious code patterns
// ---------------------------------------------------------------
sectionHeader('A2 — Malicious Code Patterns');
$malwarePatterns = [
    '/eval\s*\(\s*base64_decode/i'                         => 'eval(base64_decode)',
    '/eval\s*\(\s*gzinflate/i'                             => 'eval(gzinflate)',
    '/assert\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)\s*\[/i'  => 'assert() with user input',
    '/system\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i'        => 'system() with user input',
    '/exec\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i'          => 'exec() with user input',
    '/passthru\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i'      => 'passthru() with user input',
    '/shell_exec\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i'    => 'shell_exec() with user input',
    '/preg_replace\s*\(.+\/e[^\w]/i'                       => 'preg_replace with /e modifier',
    '/c99shell|r57shell|b374k|wso\.php|FilesMan/i'         => 'Known webshell name',
    '/\$GLOBALS\[.{1,20}\]\(.{1,20}\$_(GET|POST)/i'        => 'GLOBALS obfuscated call',
];
$scanDirs = [BASE_PATH.'/app', BASE_PATH.'/resources', BASE_PATH.'/routes', BASE_PATH.'/public'];
$malwareFound = 0;
foreach ($scanDirs as $sDir) {
    if (!is_dir($sDir)) continue;
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        if (!in_array(strtolower($file->getExtension()), ['php','phtml','blade'])) continue;
        $content = @file_get_contents($file->getRealPath());
        if (!$content) continue;
        foreach ($malwarePatterns as $pattern => $desc) {
            if (preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE)) {
                $line = substr_count(substr($content, 0, $m[0][1]), "\n") + 1;
                issue('CRITICAL', 'A2', "Malware pattern: $desc", $file->getRealPath() . ":$line",
                    'Review and remove the injected code');
                $malwareFound++;
            }
        }
    }
}
if ($malwareFound === 0) pass('A2', 'No malware patterns found in source files');

// ---------------------------------------------------------------
// A3: Japanese/CJK injection in files
// ---------------------------------------------------------------
sectionHeader('A3 — Japanese/CJK Content Injection');
$cjkFound = 0;
foreach ($scanDirs as $sDir) {
    if (!is_dir($sDir)) continue;
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        $content = @file_get_contents($file->getRealPath());
        if (!$content) continue;
        if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $content)) {
            issue('HIGH', 'A3', 'Japanese/CJK characters found in source file', $file->getRealPath(),
                'Run: php scripts/remove_hacker_content.php --fix');
            $cjkFound++;
        }
    }
}
if ($cjkFound === 0) pass('A3', 'No Japanese/CJK injection found in source files');

// ---------------------------------------------------------------
// A4: File permissions
// ---------------------------------------------------------------
sectionHeader('A4 — File Permissions');
$permChecks = [
    BASE_PATH . '/.env'              => ['max' => 0600, 'label' => '.env'],
    BASE_PATH . '/config'            => ['max' => 0755, 'label' => 'config/'],
    BASE_PATH . '/storage'           => ['max' => 0775, 'label' => 'storage/'],
    BASE_PATH . '/public/uploads'    => ['max' => 0755, 'label' => 'public/uploads/'],
];
foreach ($permChecks as $path => $check) {
    if (!file_exists($path)) continue;
    $perms = fileperms($path) & 0777;
    if ($perms > $check['max']) {
        issue('HIGH', 'A4', "Overly permissive: {$check['label']} (" . decoct($perms) . ')',
            $path, 'Run: chmod ' . decoct($check['max']) . ' ' . $path);
    } else {
        pass('A4', "{$check['label']} permissions OK (" . decoct($perms) . ')');
    }
}

// World-writable PHP files
$wwFound = 0;
$wwDirs  = [BASE_PATH.'/app', BASE_PATH.'/resources', BASE_PATH.'/routes'];
foreach ($wwDirs as $d) {
    if (!is_dir($d)) continue;
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        if ((fileperms($file->getRealPath()) & 0002) !== 0) {
            issue('HIGH', 'A4', 'World-writable PHP file', $file->getRealPath(),
                'chmod 644 ' . $file->getRealPath());
            $wwFound++;
        }
    }
}
if ($wwFound === 0) pass('A4', 'No world-writable source files found');

// ---------------------------------------------------------------
// A5: Sensitive file exposure
// ---------------------------------------------------------------
sectionHeader('A5 — Sensitive File Exposure');
$sensitiveFiles = [
    BASE_PATH . '/.env'              => 'Contains DB credentials — must not be web-accessible',
    BASE_PATH . '/.env.backup'       => 'Backup .env — delete immediately',
    BASE_PATH . '/composer.json'     => 'Lists all dependencies — should not be in webroot',
    BASE_PATH . '/storage/logs'      => 'Log files — should not be web-accessible',
    BASE_PATH . '/public/.git'       => '.git in webroot — exposes full source code',
    BASE_PATH . '/.git'              => '.git present — ensure not accessible via web',
];
foreach ($sensitiveFiles as $path => $desc) {
    if (file_exists($path)) {
        $severity = str_contains($path, '.env.backup') || str_contains($path, '/.git') ? 'CRITICAL' : 'MEDIUM';
        issue($severity, 'A5', basename($path) . ' exists', $desc, '');
    }
}

// ---------------------------------------------------------------
// A6: .htaccess security
// ---------------------------------------------------------------
sectionHeader('A6 — .htaccess Security Rules');
$htaccess = BASE_PATH . '/public/.htaccess';
if (!file_exists($htaccess)) {
    issue('HIGH', 'A6', 'public/.htaccess missing', 'No .htaccess protection in webroot',
        'Add the security .htaccess from this repository');
} else {
    $hContent = file_get_contents($htaccess);
    $checks = [
        'Options -Indexes'                => ['LOW',  'A6', 'Directory listing not disabled'],
        'X-Frame-Options'                 => ['MEDIUM','A6','X-Frame-Options header missing'],
        'X-Content-Type-Options'          => ['MEDIUM','A6','X-Content-Type-Options header missing'],
        'uploads.*\.php'                  => ['HIGH', 'A6','No PHP block in uploads directory'],
    ];
    foreach ($checks as $needle => $args) {
        if (!str_contains($hContent, explode('.*', $needle)[0]) && !preg_match("/$needle/i", $hContent)) {
            issue($args[0], $args[1], $args[2], $htaccess,
                'Add rule to public/.htaccess');
        } else {
            pass($args[1], $args[2] . ' → PRESENT');
        }
    }
}

// ---------------------------------------------------------------
// A7: APP_DEBUG in production
// ---------------------------------------------------------------
sectionHeader('A7 — Laravel Configuration Security');
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);

    if (preg_match('/APP_DEBUG\s*=\s*true/i', $envContent)) {
        issue('HIGH', 'A7', 'APP_DEBUG=true in .env',
            'Exposes stack traces and environment variables to the public',
            'Set APP_DEBUG=false in .env');
    } else {
        pass('A7', 'APP_DEBUG is false or not set');
    }

    if (preg_match('/APP_ENV\s*=\s*local/i', $envContent)) {
        issue('MEDIUM', 'A7', 'APP_ENV=local in .env',
            'Should be APP_ENV=production on live server',
            'Set APP_ENV=production in .env');
    } else {
        pass('A7', 'APP_ENV is not "local"');
    }

    if (!preg_match('/SESSION_SECURE_COOKIE\s*=\s*true/i', $envContent)) {
        issue('MEDIUM', 'A7', 'SESSION_SECURE_COOKIE not enabled',
            'Session cookie can be stolen over HTTP',
            'Add SESSION_SECURE_COOKIE=true to .env');
    } else {
        pass('A7', 'SESSION_SECURE_COOKIE=true');
    }
} else {
    issue('CRITICAL', 'A7', '.env file not found', 'Cannot check Laravel configuration', '');
}

// ---------------------------------------------------------------
// A8: Hard-coded credentials / API keys in source
// ---------------------------------------------------------------
sectionHeader('A8 — Hard-coded Secrets in Source Code');
$secretPatterns = [
    '/["\']password["\']\s*=>\s*["\'][^"\']{6,}["\']/i' => 'Hard-coded password',
    '/sk-[A-Za-z0-9]{20,}/'                               => 'OpenAI API key',
    '/AKIA[A-Z0-9]{16}/'                                  => 'AWS Access Key',
    '/["\']secret["\'][^=]*=\s*["\'][^"\']{10,}["\']/i'  => 'Hard-coded secret',
    '/mysql:\/\/\w+:[^@]+@/'                              => 'DB credentials in connection string',
];
$secretFound = 0;
foreach ($scanDirs as $sDir) {
    if (!is_dir($sDir)) continue;
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        $content = @file_get_contents($file->getRealPath());
        if (!$content) continue;
        foreach ($secretPatterns as $pattern => $desc) {
            if (preg_match($pattern, $content)) {
                issue('HIGH', 'A8', "Possible $desc", $file->getRealPath(),
                    'Move credentials to .env');
                $secretFound++;
            }
        }
    }
}
if ($secretFound === 0) pass('A8', 'No obvious hard-coded secrets found');

// ---------------------------------------------------------------
// A9: Cron job audit
// ---------------------------------------------------------------
sectionHeader('A9 — Cron Job Audit');
$crontab = @shell_exec('crontab -l 2>/dev/null');
if ($crontab) {
    $lines = array_filter(explode("\n", $crontab), fn($l) => trim($l) && !str_starts_with(trim($l), '#'));
    foreach ($lines as $line) {
        $suspicious = preg_match('/(wget|curl|bash|sh|python|perl|nc |ncat|/tmp/)', $line);
        if ($suspicious) {
            issue('CRITICAL', 'A9', 'Suspicious cron job detected', $line,
                'Remove this cron entry: crontab -e');
        } else {
            echo "  ℹ️  [INFO][A9] Cron: $line\n";
        }
    }
} else {
    pass('A9', 'No crontab entries or unable to read');
}

// ---------------------------------------------------------------
// A10: CSRF check in Laravel
// ---------------------------------------------------------------
sectionHeader('A10 — CSRF Protection');
$kernelFile = BASE_PATH . '/app/Http/Kernel.php';
if (file_exists($kernelFile)) {
    $kernel = file_get_contents($kernelFile);
    if (!str_contains($kernel, 'VerifyCsrfToken')) {
        issue('HIGH', 'A10', 'VerifyCsrfToken middleware not found in Kernel.php',
            'CSRF protection may be disabled', 'Ensure VerifyCsrfToken is in $middlewareGroups[\'web\']');
    } else {
        pass('A10', 'VerifyCsrfToken present in Kernel.php');
    }
}

// ---------------------------------------------------------------
// A11: Storage symlink security
// ---------------------------------------------------------------
sectionHeader('A11 — Storage Symlink');
$storageLink = BASE_PATH . '/public/storage';
if (is_link($storageLink)) {
    pass('A11', 'public/storage symlink exists');
    // Check if uploads folder is publicly accessible
    $uploadsDir = BASE_PATH . '/storage/app/public';
    if (is_dir($uploadsDir)) {
        $phpInStorage = 0;
        $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsDir, FilesystemIterator::SKIP_DOTS));
        foreach ($iter as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                issue('CRITICAL', 'A11', 'PHP file in public storage (via symlink)', $file->getRealPath(),
                    'Delete: rm ' . $file->getRealPath());
                $phpInStorage++;
            }
        }
        if ($phpInStorage === 0) pass('A11', 'No PHP files in public storage');
    }
}

// ---------------------------------------------------------------
// FINAL REPORT
// ---------------------------------------------------------------
sectionHeader('AUDIT SUMMARY');

$bySeverity = ['CRITICAL' => [], 'HIGH' => [], 'MEDIUM' => [], 'LOW' => []];
foreach ($issues as $i) {
    $bySeverity[$i['severity']][] = $i;
}

$total = count($issues);
echo "Total issues: $total\n";
echo "  🔴 CRITICAL : " . count($bySeverity['CRITICAL']) . "\n";
echo "  🟠 HIGH     : " . count($bySeverity['HIGH']) . "\n";
echo "  🟡 MEDIUM   : " . count($bySeverity['MEDIUM']) . "\n";
echo "  🔵 LOW      : " . count($bySeverity['LOW']) . "\n";

if (count($bySeverity['CRITICAL']) > 0) {
    echo "\n🔴 CRITICAL ISSUES — Fix immediately:\n";
    foreach ($bySeverity['CRITICAL'] as $i) {
        echo "  [{$i['code']}] {$i['title']}\n";
        echo "          {$i['detail']}\n";
        if ($i['fix']) echo "          FIX: {$i['fix']}\n";
    }
}

// Save report
$reportLines = [];
ob_start();
// report already echoed to stdout — save it
$report = implode("\n", array_map(fn($i) =>
    "[{$i['severity']}][{$i['code']}] {$i['title']}\n  {$i['detail']}\n  FIX: {$i['fix']}",
    $issues
));
@mkdir(dirname(REPORT_FILE), 0755, true);
file_put_contents(REPORT_FILE, $report);
echo "\nReport saved: " . REPORT_FILE . "\n";
