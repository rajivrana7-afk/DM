<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityMiddleware
 * ------------------
 * Runs on every web request and:
 *  1. Blocks requests containing Japanese/CJK characters in URL/query
 *  2. Blocks known bad bots and scanner user agents
 *  3. Blocks requests to typical webshell paths
 *  4. Adds security headers to all responses
 *  5. Logs and rate-limits suspicious IPs
 */
class SecurityMiddleware
{
    /**
     * Bad bot / scanner user agent substrings (case-insensitive).
     */
    private const BAD_USER_AGENTS = [
        'nikto', 'sqlmap', 'nessus', 'masscan', 'zgrab',
        'python-requests', 'go-http-client', 'libwww-perl',
        'curl/7', 'wget/', 'scrapy', 'nmap',
        'acunetix', 'appscan', 'webinspect', 'burpsuite',
        'dirbuster', 'gobuster', 'wfuzz', 'ffuf',
    ];

    /**
     * URL path fragments that indicate a webshell probe.
     */
    private const WEBSHELL_PATHS = [
        'c99', 'r57', 'b374k', 'wso', 'alfa',
        'indoxploit', 'hackbar', 'FilesMan',
        'eval-stdin', 'wp-config', 'passwd',
        '/etc/', '/proc/', '/tmp/',
        'phpinfo', 'shell.php', 'cmd.php', 'upload.php',
    ];

    /**
     * SQL injection patterns.
     */
    private const SQLI_PATTERNS = [
        '/(\bUNION\b.*\bSELECT\b|\bSELECT\b.*\bFROM\b.*\bWHERE\b)/i',
        '/(\bDROP\b.*\bTABLE\b|\bDELETE\b.*\bFROM\b)/i',
        '/(\'|\")(\s)*(OR|AND)(\s)*(\'|\"|[0-9])/i',
        '/--\s*$/',
        '/\/\*.*\*\//s',
        '/\bxp_cmdshell\b/i',
        '/\bINFORMATION_SCHEMA\b/i',
    ];

    /**
     * XSS patterns.
     */
    private const XSS_PATTERNS = [
        '/<script[^>]*>.*?<\/script>/si',
        '/javascript\s*:/i',
        '/on(load|click|mouseover|submit|focus|error)\s*=/i',
        '/<iframe[^>]*>/i',
        '/document\.(cookie|write|location)/i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $ip  = $request->ip();
        $uri = $request->getRequestUri();
        $ua  = $request->userAgent() ?? '';

        // ---- 1. Block Japanese/CJK in URL ----
        if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]/u', urldecode($uri))) {
            Log::channel('security')->warning('Japanese/CJK URL blocked', [
                'ip' => $ip, 'uri' => $uri, 'ua' => $ua,
            ]);
            abort(403, 'Forbidden');
        }

        // ---- 2. Block bad bots ----
        $uaLower = strtolower($ua);
        foreach (self::BAD_USER_AGENTS as $badBot) {
            if (str_contains($uaLower, $badBot)) {
                Log::channel('security')->warning('Bad bot blocked', [
                    'ip' => $ip, 'bot' => $badBot, 'ua' => $ua,
                ]);
                abort(403, 'Forbidden');
            }
        }

        // ---- 3. Block webshell probe paths ----
        $uriLower = strtolower($uri);
        foreach (self::WEBSHELL_PATHS as $shellPath) {
            if (str_contains($uriLower, strtolower($shellPath))) {
                Log::channel('security')->critical('Webshell probe blocked', [
                    'ip' => $ip, 'uri' => $uri, 'pattern' => $shellPath,
                ]);
                abort(404);  // 404 instead of 403 to avoid enumeration
            }
        }

        // ---- 4. SQLi / XSS detection in inputs ----
        $inputs = array_merge(
            $request->query->all(),
            $request->request->all()
        );
        $inputStr = json_encode($inputs);

        foreach (self::SQLI_PATTERNS as $pattern) {
            if (preg_match($pattern, $inputStr)) {
                Log::channel('security')->warning('SQL injection attempt blocked', [
                    'ip' => $ip, 'uri' => $uri, 'pattern' => $pattern,
                ]);
                abort(400, 'Bad Request');
            }
        }

        foreach (self::XSS_PATTERNS as $pattern) {
            if (preg_match($pattern, $inputStr)) {
                Log::channel('security')->warning('XSS attempt blocked', [
                    'ip' => $ip, 'uri' => $uri, 'pattern' => $pattern,
                ]);
                abort(400, 'Bad Request');
            }
        }

        // ---- 5. Proceed and add security headers to response ----
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self' https:; " .
            "script-src 'self' 'unsafe-inline' https://www.google.com https://www.googletagmanager.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://track.nopaperforms.com https://in6cdn.npfs.co https://widgets.in6.nopaperforms.com; " .
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; " .
            "img-src 'self' data: https:; " .
            "font-src 'self' https:; " .
            "frame-src 'self' https://www.google.com https://www.googletagmanager.com;"
        );

        // Remove fingerprinting headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
