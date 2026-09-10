<?php

namespace App\Libraries;

use App\Models\SettingModel;

/**
 * Provider-agnostic cargo insurance quote client.
 *
 * Settings (group=insurance):
 *   insurance_provider — generic | icici_lombard | tata_aig | bajaj_allianz
 *   insurance_endpoint — base URL
 *   insurance_api_key  — bearer token (encrypted at rest)
 *   insurance_default_premium_pct — fallback ₹/100 of cargo value when API not configured (default 0.05%)
 *
 * If the API isn't configured, we compute a simple % of cargo value as a stub,
 * which lets the workflow be tested end-to-end without a real insurer integration.
 */
class InsuranceService
{
    private function setting(string $k, string $default = ''): string
    {
        return (string) ((new SettingModel())->get($k, $default) ?? $default);
    }

    public function isConfigured(): bool
    {
        return $this->setting('insurance_endpoint') !== '' && $this->setting('insurance_api_key') !== '';
    }

    /**
     * Returns ['ok' => bool, 'premium' => float, 'provider' => string, ...]
     * Tries the configured API; falls back to a deterministic percentage stub.
     */
    public function quote(float $cargoValue, string $pickup, string $drop, ?string $vehicleType = null): array
    {
        if ($cargoValue <= 0) return ['ok' => false, 'error' => 'cargo_value must be > 0'];

        $provider = $this->setting('insurance_provider', 'generic') ?: 'generic';

        if (!$this->isConfigured()) {
            // Stub fallback: configurable % of cargo value
            $pct = (float) ($this->setting('insurance_default_premium_pct', '0.05') ?: 0.05);
            $premium = round($cargoValue * $pct / 100, 2);
            return [
                'ok'       => true,
                'provider' => $provider . ' (stub)',
                'premium'  => $premium,
                'note'     => 'Stub quote — configure insurance API for live rates.',
                'raw'      => ['cargo_value' => $cargoValue, 'pickup' => $pickup, 'drop' => $drop, 'pct' => $pct],
            ];
        }

        $endpoint = rtrim($this->setting('insurance_endpoint'), '/') . '/quote';
        $payload = [
            'cargo_value' => $cargoValue,
            'pickup_city' => $pickup,
            'drop_city'   => $drop,
            'vehicle'     => $vehicleType,
        ];
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->setting('insurance_api_key'),
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($res === false || $code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => 'http_' . $code, 'raw' => substr((string) $res, 0, 400)];
        }
        $body = json_decode((string) $res, true);
        if (!is_array($body) || empty($body['premium'])) {
            return ['ok' => false, 'error' => 'bad_response', 'raw' => $body];
        }
        return [
            'ok'       => true,
            'provider' => (string) ($body['provider'] ?? $provider),
            'premium'  => (float) $body['premium'],
            'policy_no'=> $body['policy_no'] ?? null,
            'valid_until' => $body['valid_until'] ?? null,
            'raw'      => $body,
        ];
    }
}
