<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Centralised file-upload validation. Replaces the ad-hoc
 * `$file->isValid() && in_array($ext, …)` pattern that's scattered across
 * controllers (TDS, POD, vendor bill, expense receipt, profile, etc.).
 *
 * Three layers of defence:
 *  1. Server-side size cap (overrides php.ini; defaults to 10 MB)
 *  2. Real MIME sniff via `finfo` — extension can lie, magic bytes don't
 *  3. Allowed-extensions whitelist + lowercase
 *
 * Bonus: optional EXIF strip for JPEGs (privacy + GPS-leak prevention).
 */
class SafeUpload
{
    /** MIME ↔ extension map. Reject anything outside this set. */
    public const PROFILES = [
        'document' => [
            'pdf'  => ['application/pdf'],
            'png'  => ['image/png'],
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'webp' => ['image/webp'],
        ],
        'image' => [
            'png'  => ['image/png'],
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'webp' => ['image/webp'],
            'gif'  => ['image/gif'],
        ],
        'csv' => [
            'csv' => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
            'txt' => ['text/plain'],
        ],
        'spreadsheet' => [
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'xls'  => ['application/vnd.ms-excel'],
            'csv'  => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
        ],
    ];

    /**
     * Validate + move an uploaded file. Returns the relative path under
     * WRITEPATH/uploads/, or throws \RuntimeException with a user-friendly
     * message for the controller to flash back.
     *
     * @param  string $subdir       e.g. 'tds', 'pod', 'expenses' — created if missing
     * @param  string $profile      Key from self::PROFILES
     * @param  int    $maxSizeBytes Hard cap (defaults 10 MB)
     */
    public static function move(
        UploadedFile $file,
        string $subdir,
        string $profile = 'document',
        int $maxSizeBytes = 10 * 1024 * 1024
    ): string {
        if (!$file->isValid()) {
            throw new \RuntimeException('Upload failed: ' . $file->getErrorString());
        }
        if ($file->getSize() > $maxSizeBytes) {
            throw new \RuntimeException(sprintf('File too large (%s) — max %s.',
                self::humanBytes($file->getSize()), self::humanBytes($maxSizeBytes)));
        }

        $allowed = self::PROFILES[$profile] ?? null;
        if (!$allowed) throw new \RuntimeException("Unknown upload profile: $profile");

        $ext = strtolower($file->getExtension() ?: $file->getClientExtension());
        if (!isset($allowed[$ext])) {
            throw new \RuntimeException('File type not allowed. Permitted: ' . implode(', ', array_keys($allowed)));
        }

        // Real MIME sniff — extension alone is bypassable
        $realMime = self::sniffMime($file->getTempName());
        if (!in_array($realMime, $allowed[$ext], true)) {
            throw new \RuntimeException("File content (\"$realMime\") doesn't match its extension. Reject.");
        }

        $dir = WRITEPATH . 'uploads/' . trim($subdir, '/');
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        // Random filename — avoid path traversal + collisions
        $name = bin2hex(random_bytes(8)) . '-' . date('Ymd-His') . '.' . $ext;
        $file->move($dir, $name, true);
        $path = trim($subdir, '/') . '/' . $name;

        // EXIF strip for JPEGs — drops GPS coords from phone-camera uploads
        if (in_array($ext, ['jpg', 'jpeg'], true) && function_exists('imagecreatefromjpeg')) {
            self::stripJpegExif($dir . '/' . $name);
        }

        return $path;
    }

    /** Helper: human-readable byte size. */
    public static function humanBytes(int $b): string
    {
        if ($b >= 1048576) return number_format($b / 1048576, 1) . ' MB';
        if ($b >= 1024)    return number_format($b / 1024, 1)    . ' KB';
        return $b . ' B';
    }

    private static function sniffMime(string $path): string
    {
        if (!function_exists('finfo_open')) return '';
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $m  = $fi ? finfo_file($fi, $path) : '';
        if ($fi) finfo_close($fi);
        return (string) $m;
    }

    /** Re-encodes a JPEG via GD which drops every metadata segment. */
    private static function stripJpegExif(string $path): void
    {
        try {
            $img = @imagecreatefromjpeg($path);
            if (!$img) return;
            imagejpeg($img, $path, 90);
            imagedestroy($img);
        } catch (\Throwable $e) {
            // Stripping is best-effort — don't fail the upload over it
            log_message('warning', 'EXIF strip failed for ' . $path . ': ' . $e->getMessage());
        }
    }
}
