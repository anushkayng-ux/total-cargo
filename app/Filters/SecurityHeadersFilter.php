<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Project-wide security headers. Applied as an `after` global filter.
 *
 * Why each header:
 *  - HSTS                 — force HTTPS in browsers that have seen us once
 *  - X-Content-Type-Options — defeat MIME-sniffing on user-uploaded files
 *  - X-Frame-Options      — block clickjacking via <iframe>
 *  - Referrer-Policy      — don't leak full URLs (with tokens) when users click out
 *  - Permissions-Policy   — opt out of features we never use (camera, geo, etc.)
 *  - X-Permitted-Cross-Domain-Policies — Adobe legacy attack surface
 *  - Cross-Origin-*       — modern XS-Leaks defence
 *  - CSP                  — last line of defence against XSS. Pragmatic (not strict)
 *    because the app uses inline event handlers and CDN scripts. Hardening
 *    further would need a refactor pass.
 *
 * Endpoints with their own framing requirements (driver PWA, e-POD, public
 * tracking, meeting PWA) are exempted via `$except` so embedded views don't
 * break.
 */
class SecurityHeadersFilter implements FilterInterface
{
    /** URI prefixes that need looser policies (public PWAs, embeds). */
    private const RELAXED_PREFIXES = ['d/', 'm/', 'epod/', 'quote/', 'feedback/', 'track/', 'email-track/'];

    public function before(RequestInterface $request, $arguments = null)
    {
        // no-op; this is a response-shaping filter
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $uri  = ltrim((string) $request->getUri()->getPath(), '/');
        $base = trim((string) parse_url(base_url(), PHP_URL_PATH), '/');
        if ($base && str_starts_with($uri, $base)) $uri = ltrim(substr($uri, strlen($base)), '/');
        $relaxed = false;
        foreach (self::RELAXED_PREFIXES as $p) if (str_starts_with($uri, $p)) { $relaxed = true; break; }

        // Always-on protections — cheap, no compatibility risk
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options',         $relaxed ? 'SAMEORIGIN' : 'DENY');
        $response->setHeader('Referrer-Policy',         'strict-origin-when-cross-origin');
        $response->setHeader('X-Permitted-Cross-Domain-Policies', 'none');
        $response->setHeader('Permissions-Policy', implode(', ', [
            'camera=()', 'microphone=()', 'payment=()', 'usb=()', 'magnetometer=()',
            'gyroscope=(self)', 'accelerometer=(self)', 'geolocation=(self)', 'fullscreen=(self)',
        ]));

        // Cross-Origin isolation — relax for endpoints that embed third-party content
        if (!$relaxed) {
            $response->setHeader('Cross-Origin-Opener-Policy',   'same-origin');
            $response->setHeader('Cross-Origin-Resource-Policy', 'same-site');
        }

        // HSTS only when actually served over HTTPS — avoid trapping dev on http://
        if ($request->getUri()->getScheme() === 'https') {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Pragmatic CSP. Inline styles + inline event handlers are still used in
        // a few views, plus CDN-served Bootstrap/Chart.js. `unsafe-inline` stays
        // for style+script-attr until those are extracted. Reduce-not-eliminate.
        $csp = implode('; ', [
            "default-src 'self'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "style-src-attr 'unsafe-inline'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "script-src-attr 'unsafe-inline'",
            "connect-src 'self' https:",
            "frame-ancestors " . ($relaxed ? "'self'" : "'none'"),
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
        $response->setHeader('Content-Security-Policy', $csp);

        return $response;
    }
}
