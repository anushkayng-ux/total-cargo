<?php

namespace App\Libraries;

use App\Models\SettingModel;
use App\Models\TripModel;

/**
 * Picks the best available GPS source per trip, in the priority order set by
 * the operator (settings.gps_priority — CSV of: loconav, fasttag).
 *
 * Driver-phone tracking is NOT polled here — it pushes coords from the PWA on
 * its own. The router skips any trip that has an active driver_track_token.
 */
class GpsRouter
{
    /** Default priority if not configured. */
    public const DEFAULT_PRIORITY = ['loconav', 'fasttag'];

    public function priority(): array
    {
        $csv = (string) ((new SettingModel())->get('gps_priority', '') ?? '');
        if ($csv === '') return self::DEFAULT_PRIORITY;
        $list = array_filter(array_map('trim', explode(',', strtolower($csv))));
        return array_values(array_intersect($list, self::DEFAULT_PRIORITY));
    }

    /** Refresh every active trip across whichever sources are configured. */
    public function refreshAllActive(): array
    {
        $loco = new LocoNavService();
        $ftag = new FastTagService();

        $tripModel = new TripModel();
        $trips = $tripModel->select('id, vehicle_number, driver_track_token, driver_track_last_ping')
            ->whereNotIn('current_status', ['Closed', 'Cancelled'])
            ->where('deleted_at IS NULL')
            ->where('vehicle_number IS NOT NULL')
            ->where("vehicle_number != ''")
            ->findAll();

        $stats = ['scanned' => 0, 'loconav' => 0, 'fasttag' => 0, 'skipped_phone' => 0, 'failed' => 0];
        $priority = $this->priority();
        $phoneFresh = 5 * 60; // skip polling if a driver-phone ping arrived in the last 5 min

        foreach ($trips as $t) {
            $stats['scanned']++;

            // If the driver phone is actively pinging, leave it alone — phone is the freshest source.
            if (!empty($t['driver_track_token']) && !empty($t['driver_track_last_ping'])
                && (time() - strtotime($t['driver_track_last_ping']) < $phoneFresh)) {
                $stats['skipped_phone']++;
                continue;
            }

            $hit = false;
            foreach ($priority as $source) {
                if ($source === 'loconav' && $loco->isConfigured()) {
                    $r = $loco->refreshVehicle((string) $t['vehicle_number'], (int) $t['id']);
                    if (!empty($r['ok'])) { $stats['loconav']++; $hit = true; break; }
                } elseif ($source === 'fasttag' && $ftag->isConfigured()) {
                    $r = $ftag->refreshVehicle((string) $t['vehicle_number'], (int) $t['id']);
                    if (!empty($r['ok'])) { $stats['fasttag']++; $hit = true; break; }
                }
            }
            if (!$hit) $stats['failed']++;
        }
        return $stats;
    }
}
