<?php

namespace App\Libraries;

/**
 * Password-hygiene helpers — k-anonymity HIBP check + zxcvbn-lite strength.
 *
 * HIBP usage: we hash the password to SHA-1, send only the first 5 hex chars
 * to api.pwnedpasswords.com, and scan the returned list for the rest. The
 * plaintext password never leaves the server.
 *
 * Failing-open is intentional: if the HIBP API is unreachable, we *don't*
 * block the user. Better to let a save through than DDoS our own login flow.
 */
class PasswordHygiene
{
    /** @return bool true if the password is found in a known breach. */
    public static function isBreached(string $password, int $timeout = 3): bool
    {
        if (strlen($password) < 4) return true; // trivial
        $sha = strtoupper(sha1($password));
        $prefix = substr($sha, 0, 5);
        $suffix = substr($sha, 5);

        $url = 'https://api.pwnedpasswords.com/range/' . $prefix;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Add-Padding: true', 'User-Agent: TPT-Aggregator'],
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT        => $timeout,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || !is_string($body)) return false; // fail open

        foreach (preg_split('/\r?\n/', $body) ?: [] as $line) {
            [$hashSuffix, $count] = array_pad(explode(':', trim($line), 2), 2, '0');
            if (strcasecmp($hashSuffix, $suffix) === 0 && (int) $count > 0) return true;
        }
        return false;
    }

    /**
     * Trivial strength score — returns 0 (very weak) … 4 (strong).
     * Use to color the meter on the change-password screen.
     */
    public static function strength(string $password): int
    {
        $len = strlen($password);
        $score = 0;
        if ($len >= 8)  $score++;
        if ($len >= 12) $score++;
        if (preg_match('/[A-Z]/', $password) && preg_match('/[a-z]/', $password)) $score++;
        if (preg_match('/\d/', $password) && preg_match('/[^\w]/', $password))    $score++;
        return min(4, $score);
    }
}
