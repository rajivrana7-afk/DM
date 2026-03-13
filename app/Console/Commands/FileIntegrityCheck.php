<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * FileIntegrityCheck
 * ------------------
 * Generates and verifies SHA-256 checksums of all PHP source files.
 *
 * Usage:
 *   php artisan security:integrity --baseline   Generate initial checksums
 *   php artisan security:integrity              Check current files vs baseline
 *   php artisan security:integrity --email=admin@dpmiindia.com  Email report
 */
class FileIntegrityCheck extends Command
{
    protected $signature   = 'security:integrity
                              {--baseline : Generate a new baseline checksum file}
                              {--email=  : Send alert to this email address}';

    protected $description = 'Check PHP file integrity to detect unauthorized modifications';

    private const BASELINE_FILE = 'storage/security/file_checksums.json';
    private const SCAN_DIRS = ['app', 'resources', 'routes', 'config', 'public'];
    private const SCAN_EXTS = ['php', 'blade', 'json', 'htaccess'];

    public function handle(): int
    {
        $baselinePath = base_path(self::BASELINE_FILE);

        if ($this->option('baseline')) {
            return $this->generateBaseline($baselinePath);
        }

        return $this->runIntegrityCheck($baselinePath);
    }

    // ---------------------------------------------------------------
    // Generate baseline
    // ---------------------------------------------------------------
    private function generateBaseline(string $baselinePath): int
    {
        $this->info('Generating integrity baseline...');

        @mkdir(dirname($baselinePath), 0700, true);

        $checksums = [];
        $count     = 0;

        foreach (self::SCAN_DIRS as $dir) {
            $fullDir = base_path($dir);
            if (!is_dir($fullDir)) continue;

            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullDir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iter as $file) {
                if (!$file->isFile()) continue;
                if (!in_array(strtolower($file->getExtension()), self::SCAN_EXTS)) continue;

                $relativePath = str_replace(base_path() . '/', '', $file->getRealPath());
                $checksums[$relativePath] = [
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'size'   => $file->getSize(),
                    'mtime'  => $file->getMTime(),
                ];
                $count++;
            }
        }

        $baseline = [
            'generated_at' => now()->toIso8601String(),
            'file_count'   => $count,
            'files'        => $checksums,
        ];

        file_put_contents($baselinePath, json_encode($baseline, JSON_PRETTY_PRINT));
        $this->info("Baseline saved: $baselinePath ($count files checksummed)");

        return self::SUCCESS;
    }

    // ---------------------------------------------------------------
    // Run integrity check
    // ---------------------------------------------------------------
    private function runIntegrityCheck(string $baselinePath): int
    {
        if (!file_exists($baselinePath)) {
            $this->error('No baseline found. Run: php artisan security:integrity --baseline');
            return self::FAILURE;
        }

        $baseline = json_decode(file_get_contents($baselinePath), true);
        $this->info("Baseline from: {$baseline['generated_at']} ({$baseline['file_count']} files)");

        $modified = [];
        $added    = [];
        $missing  = [];

        // Check all current files
        $current = [];
        foreach (self::SCAN_DIRS as $dir) {
            $fullDir = base_path($dir);
            if (!is_dir($fullDir)) continue;

            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullDir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iter as $file) {
                if (!$file->isFile()) continue;
                if (!in_array(strtolower($file->getExtension()), self::SCAN_EXTS)) continue;

                $relativePath = str_replace(base_path() . '/', '', $file->getRealPath());
                $currentHash  = hash_file('sha256', $file->getRealPath());
                $current[$relativePath] = $currentHash;

                if (!isset($baseline['files'][$relativePath])) {
                    // New file added since baseline
                    $added[] = [
                        'file' => $relativePath,
                        'hash' => $currentHash,
                        'mtime' => date('Y-m-d H:i:s', $file->getMTime()),
                    ];
                } elseif ($baseline['files'][$relativePath]['sha256'] !== $currentHash) {
                    // File modified
                    $modified[] = [
                        'file'    => $relativePath,
                        'old'     => $baseline['files'][$relativePath]['sha256'],
                        'new'     => $currentHash,
                        'mtime'   => date('Y-m-d H:i:s', $file->getMTime()),
                    ];
                }
            }
        }

        // Check for deleted files
        foreach ($baseline['files'] as $path => $data) {
            if (!isset($current[$path])) {
                $missing[] = $path;
            }
        }

        // Report
        $hasIssues = !empty($modified) || !empty($added);

        if (!empty($modified)) {
            $this->error('MODIFIED FILES (' . count($modified) . '):');
            foreach ($modified as $f) {
                $this->error("  ⚠️  {$f['file']} (modified: {$f['mtime']})");
                Log::channel('security')->critical('File integrity violation: modified', $f);
            }
        }

        if (!empty($added)) {
            $this->warn('NEW FILES ADDED (' . count($added) . '):');
            foreach ($added as $f) {
                $this->warn("  ➕ {$f['file']} (added: {$f['mtime']})");
                Log::channel('security')->warning('File integrity: new file detected', $f);
            }
        }

        if (!empty($missing)) {
            $this->warn('DELETED FILES (' . count($missing) . '):');
            foreach ($missing as $path) {
                $this->warn("  ➖ $path");
            }
        }

        if (!$hasIssues && empty($missing)) {
            $this->info('✅ All ' . count($current) . ' files match baseline. Integrity OK.');
        }

        // Email alert if issues found and email provided
        if ($hasIssues && $email = $this->option('email')) {
            $this->sendAlert($email, $modified, $added);
        }

        return $hasIssues ? self::FAILURE : self::SUCCESS;
    }

    private function sendAlert(string $email, array $modified, array $added): void
    {
        try {
            \Mail::raw(
                "SECURITY ALERT: File integrity violation on dpmiindia.com\n\n" .
                "Modified files:\n" . implode("\n", array_column($modified, 'file')) . "\n\n" .
                "New files:\n"      . implode("\n", array_column($added, 'file')),
                fn($m) => $m->to($email)->subject('[DPMI SECURITY] File Integrity Alert')
            );
            $this->info("Alert sent to $email");
        } catch (\Throwable $e) {
            $this->error("Could not send alert: " . $e->getMessage());
        }
    }
}
