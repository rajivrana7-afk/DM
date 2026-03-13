<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SecurityAutoHeal
 * ----------------
 * Scheduled command — runs automatically via cron to detect and
 * neutralize threats without manual intervention.
 *
 * Schedule in app/Console/Kernel.php:
 *   $schedule->command('security:autoheal')->hourly();
 *   $schedule->command('security:integrity --email=admin@dpmiindia.com')->daily();
 *
 * Cron entry (add via: crontab -e):
 *   * * * * * cd /path/to/dpmi && php artisan schedule:run >> /dev/null 2>&1
 */
class SecurityAutoHeal extends Command
{
    protected $signature   = 'security:autoheal {--dry-run : Show what would be done without making changes}';
    protected $description = 'Auto-detect and neutralize PHP webshells, injected files, and bad permissions';

    // PHP files in these directories are always suspicious
    private const UPLOAD_DIRS = [
        'public/uploads',
        'storage/app/public',
        'storage/app',
    ];

    // If a PHP file matches any of these patterns in upload dirs → quarantine
    private const INSTANT_QUARANTINE_PATTERNS = [
        '/eval\s*\(\s*base64_decode/i',
        '/assert\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i',
        '/system\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i',
        '/passthru\s*\(\s*\$_(GET|POST|REQUEST)/i',
        '/c99shell|r57shell|b374k|FilesMan/i',
        '/preg_replace\s*\(.+\/e[^\w]/i',
    ];

    private int $quarantined = 0;
    private int $fixed       = 0;
    private array $alerts    = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $mode   = $dryRun ? '[DRY-RUN]' : '[LIVE]';

        $this->info("$mode SecurityAutoHeal started at " . now()->toDateTimeString());

        $this->checkUploadDirsForPhp($dryRun);
        $this->fixWorldWritableFiles($dryRun);
        $this->checkEnvFilePermissions($dryRun);
        $this->rotateOldLogs();

        $this->info("Done. Quarantined: {$this->quarantined} | Fixed: {$this->fixed}");

        if (!empty($this->alerts)) {
            Log::channel('security')->critical('SecurityAutoHeal alerts', $this->alerts);
        }

        return self::SUCCESS;
    }

    // ---------------------------------------------------------------
    // 1. Quarantine PHP files found in upload directories
    // ---------------------------------------------------------------
    private function checkUploadDirsForPhp(bool $dryRun): void
    {
        foreach (self::UPLOAD_DIRS as $dir) {
            $fullDir = base_path($dir);
            if (!is_dir($fullDir)) continue;

            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullDir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iter as $file) {
                if (!$file->isFile()) continue;
                $ext = strtolower($file->getExtension());

                // Any PHP-executable file in uploads = webshell risk
                if (!in_array($ext, ['php','phtml','php3','php4','php5','php7','pht','phar'])) {
                    continue;
                }

                $path    = $file->getRealPath();
                $content = @file_get_contents($path) ?: '';

                // If content matches malware pattern, quarantine immediately
                $isDefinitelyMalware = false;
                foreach (self::INSTANT_QUARANTINE_PATTERNS as $pattern) {
                    if (preg_match($pattern, $content)) {
                        $isDefinitelyMalware = true;
                        break;
                    }
                }

                $action = $isDefinitelyMalware ? 'QUARANTINE (malware confirmed)' : 'QUARANTINE (PHP in uploads)';
                $this->error("⛔ $action: $path");
                $this->alerts[] = ['action' => $action, 'file' => $path];

                if (!$dryRun) {
                    $this->quarantineFile($path);
                }
            }
        }
    }

    // ---------------------------------------------------------------
    // 2. Fix world-writable files
    // ---------------------------------------------------------------
    private function fixWorldWritableFiles(bool $dryRun): void
    {
        $dirs = [base_path('app'), base_path('resources'), base_path('routes'), base_path('config')];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;

            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iter as $file) {
                if (!$file->isFile()) continue;
                if ((fileperms($file->getRealPath()) & 0002) !== 0) {
                    $this->warn("🔒 World-writable file: " . $file->getRealPath());
                    if (!$dryRun) {
                        chmod($file->getRealPath(), 0644);
                        $this->fixed++;
                        Log::channel('security')->warning('Fixed world-writable file', ['file' => $file->getRealPath()]);
                    }
                }
            }
        }
    }

    // ---------------------------------------------------------------
    // 3. Ensure .env is not world-readable
    // ---------------------------------------------------------------
    private function checkEnvFilePermissions(bool $dryRun): void
    {
        $envFile = base_path('.env');
        if (!file_exists($envFile)) return;

        $perms = fileperms($envFile) & 0777;
        if ($perms > 0640) {
            $this->warn("🔒 .env permissions too open: " . decoct($perms) . " → fixing to 0600");
            if (!$dryRun) {
                chmod($envFile, 0600);
                $this->fixed++;
                Log::channel('security')->warning('.env permissions fixed', ['from' => decoct($perms), 'to' => '0600']);
            }
        }
    }

    // ---------------------------------------------------------------
    // 4. Rotate old security logs (keep last 30 days)
    // ---------------------------------------------------------------
    private function rotateOldLogs(): void
    {
        $logDir = storage_path('logs');
        if (!is_dir($logDir)) return;

        $cutoff = time() - (30 * 86400);
        foreach (glob($logDir . '/security_*.log') as $logFile) {
            if (filemtime($logFile) < $cutoff) {
                @unlink($logFile);
            }
        }
    }

    // ---------------------------------------------------------------
    // Quarantine helper
    // ---------------------------------------------------------------
    private function quarantineFile(string $path): void
    {
        $quarantineDir = storage_path('quarantine');
        @mkdir($quarantineDir, 0700, true);

        $dest = $quarantineDir . '/' . date('Ymd_His') . '_' . md5($path) . '_' . basename($path);

        if (rename($path, $dest)) {
            $this->quarantined++;
            Log::channel('security')->critical('File quarantined by auto-heal', [
                'original'   => $path,
                'quarantine' => $dest,
            ]);
        } else {
            // If can't move, at least zero-out the file to neutralize it
            file_put_contents($path, '<?php // Neutralized by security auto-heal ?>');
            $this->quarantined++;
            Log::channel('security')->critical('File neutralized (quarantine failed)', ['file' => $path]);
        }
    }
}
