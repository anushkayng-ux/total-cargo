<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Throttle\Throttler;

/**
 * Lightweight per-IP throttle for public-facing POSTs.
 * Argument: "<requests>:<perSeconds>" e.g. throttle:30,60 → 30 reqs per 60s
 */
class ThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $reqs   = (int) ($arguments[0] ?? 30);
        $window = (int) ($arguments[1] ?? 60);
        if ($reqs < 1)   $reqs = 30;
        if ($window < 1) $window = 60;

        $throttler = new Throttler(service('cache'));
        $bucket = 'throttle.' . md5($request->getIPAddress() . '|' . trim((string) $request->getUri()->getPath(), '/'));

        if ($throttler->check($bucket, $reqs, $window) === false) {
            return service('response')
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) max(1, $throttler->getTokenTime()))
                ->setBody('Too many requests. Please wait a moment.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
