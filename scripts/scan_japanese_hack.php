<?php
/**
 * Japanese SEO Hack Scanner
 *
 * Run from command line: php scripts/scan_japanese_hack.php
 *
 * This script scans for:
 * 1. Injected Japanese/CJK characters in PHP/HTML files
 * 2. Common webshell signatures
 * 3. Suspicious base64-encoded payloads
 * 4. Recently modified files (last 30 days)
 * 5. PHP files in upload directories
 */

define('BASE_PATH', dirname(__DIR__));
define('SCAN_DIRS', [
    BASE_PATH . '/app',
    BASE_PATH . '/resources',
    BASE_PATH . '/public',
    BASE_PATH . '/routes',
    BASE_PATH . '/database',
    BASE_PATH . '/storage/framework/views',  // compiled Blade views
]);
define('REPORT_PATH', BASE_PATH . '/storage/logs/hack_scan_' . date('Y-m-d_H-i-s') . '.txt');

$findings = [];
$scanned  = 0;

// ---------------------------------------------------------------
// 1. Japanese / CJK character detection (UTF-8 ranges)
// ---------------------------------------------------------------
function containsJapaneseCJK(string $content): bool
{
    return (bool) preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $content);
}

// ---------------------------------------------------------------
// 2. Known webshell / malware patterns
// ---------------------------------------------------------------
$malwarePatterns = [
    '/eval\s*\(\s*base64_decode/i'               => 'eval(base64_decode())',
    '/eval\s*\(\s*gzinflate/i'                   => 'eval(gzinflate())',
    '/eval\s*\(\s*str_rot13/i'                   => 'eval(str_rot13())',
    '/eval\s*\(\s*rawurldecode/i'                => 'eval(rawurldecode())',
    '/\$\{.+\}\s*\(/i'                           => 'Variable function call obfuscation',
    '/preg_replace\s*\(.+\/e[^\w]/i'             => 'preg_replace /e modifier (code exec)',
    '/assert\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/' => 'assert() with user input',
    '/system\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/' => 'system() with user input',
    '/passthru\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/' => 'passthru() with user input',
    '/exec\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/'  => 'exec() with user input',
    '/shell_exec\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/' => 'shell_exec() with user input',
    '/c99shell|r57shell|b374k|wso\.php|alfa\.php|indoxploit/i' => 'Known webshell name',
    '/FilesMan|passthru|chmod\(0777/i'           => 'FilesMan webshell pattern',
    '/<\?php.{0,20}eval\s*\(/s'                  => 'Inline eval at file start',
];

// ---------------------------------------------------------------
// 3. PHP files that should NOT be in upload folders
// ---------------------------------------------------------------
function isUploadPath(string $path): bool
{
    return str_contains($path, '/uploads/') || str_contains($path, '/storage/app/public/');
}

// ---------------------------------------------------------------
// Scanner
// ---------------------------------------------------------------
function scanDirectory(string $dir, array &$findings, int &$scanned, array $malwarePatterns): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $ext  = strtolower($file->getExtension());
        $path = $file->getRealPath();

        // Flag PHP files in upload directories immediately
        if ($ext === 'php' && isUploadPath($path)) {
            $findings[] = [
                'type'  => 'PHP_IN_UPLOADS',
                'file'  => $path,
                'line'  => 0,
                'match' => 'PHP file found in uploads directory — likely a webshell',
            ];
        }

        // Only deep-scan text-based files
        if (!in_array($ext, ['php', 'phtml', 'html', 'htm', 'blade', 'twig', 'js', 'json', 'htaccess'], true)) {
            continue;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            continue;
        }

        $scanned++;

        // Japanese/CJK check
        if (containsJapaneseCJK($content)) {
            $lines = explode("\n", $content);
            foreach ($lines as $lineNo => $line) {
                if (containsJapaneseCJK($line)) {
                    $findings[] = [
                        'type'  => 'JAPANESE_CJK_INJECTION',
                        'file'  => $path,
                        'line'  => $lineNo + 1,
                        'match' => trim(substr($line, 0, 200)),
                    ];
                }
            }
        }

        // Malware pattern check
        foreach ($malwarePatterns as $pattern => $description) {
            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $lineNo = substr_count(substr($content, 0, $matches[0][1]), "\n") + 1;
                $findings[] = [
                    'type'  => 'MALWARE_PATTERN',
                    'file'  => $path,
                    'line'  => $lineNo,
                    'match' => $description . ' → ' . trim(substr($matches[0][0], 0, 150)),
                ];
            }
        }

        // Recently modified files (last 30 days) — informational
        if ((time() - $file->getMTime()) < (30 * 86400) && $ext === 'php') {
            $findings[] = [
                'type'  => 'RECENTLY_MODIFIED',
                'file'  => $path,
                'line'  => 0,
                'match' => 'Modified: ' . date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }
    }
}

// Run scan
echo "Starting Japanese SEO Hack scan...\n";
echo "Base path: " . BASE_PATH . "\n\n";

foreach (SCAN_DIRS as $dir) {
    echo "Scanning: $dir\n";
    scanDirectory($dir, $findings, $scanned, $malwarePatterns);
}

// ---------------------------------------------------------------
// Report
// ---------------------------------------------------------------
$critical = array_filter($findings, fn($f) => $f['type'] !== 'RECENTLY_MODIFIED');
$modified = array_filter($findings, fn($f) => $f['type'] === 'RECENTLY_MODIFIED');

$report  = "=======================================================\n";
$report .= " JAPANESE SEO HACK SCAN REPORT\n";
$report .= " Generated: " . date('Y-m-d H:i:s') . "\n";
$report .= "=======================================================\n\n";
$report .= "Files scanned : $scanned\n";
$report .= "Critical issues: " . count($critical) . "\n";
$report .= "Recently modified PHP files: " . count($modified) . "\n\n";

if (count($critical) > 0) {
    $report .= "--- CRITICAL FINDINGS ---\n\n";
    foreach ($critical as $f) {
        $report .= "[{$f['type']}]\n";
        $report .= "  File : {$f['file']}\n";
        $report .= "  Line : {$f['line']}\n";
        $report .= "  Match: {$f['match']}\n\n";
    }
} else {
    $report .= "No critical issues found in scanned directories.\n\n";
}

if (count($modified) > 0) {
    $report .= "--- RECENTLY MODIFIED PHP FILES (last 30 days) ---\n\n";
    foreach ($modified as $f) {
        $report .= "  {$f['file']} — {$f['match']}\n";
    }
}

$report .= "\n=======================================================\n";
$report .= "REMEDIATION STEPS FOR JAPANESE SEO HACK:\n";
$report .= "=======================================================\n";
$report .= "1. Delete any PHP files found in /uploads/ directories.\n";
$report .= "2. Replace all files matching MALWARE_PATTERN with clean originals.\n";
$report .= "3. Check database: run SELECT * FROM pages WHERE content LIKE '%ラ%'\n";
$report .= "   to find injected Japanese text in your CMS database.\n";
$report .= "4. Change ALL passwords: cPanel, FTP, database, Laravel APP_KEY.\n";
$report .= "5. Add .htaccess rules from public/.htaccess (included in this PR).\n";
$report .= "6. Request Google to remove spam URLs via Search Console:\n";
$report .= "   https://search.google.com/search-console/remove-outdated-content\n";
$report .= "7. Submit a fresh sitemap in Google Search Console to re-crawl.\n";
$report .= "8. Consider installing a WAF (Cloudflare, Sucuri, or similar).\n";

// Output to console
echo $report;

// Save to log file
@mkdir(dirname(REPORT_PATH), 0755, true);
file_put_contents(REPORT_PATH, $report);
echo "\nReport saved to: " . REPORT_PATH . "\n";
