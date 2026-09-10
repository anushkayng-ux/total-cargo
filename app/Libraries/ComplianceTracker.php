<?php

namespace App\Libraries;

/**
 * Aggregates upcoming document expiries (driver licenses + vehicle RC/insurance/
 * fitness/permit/PUC/road-tax) so the dashboard widget and weekly digest can
 * draw from a single source.
 *
 * Buckets used everywhere:
 *   - expired  : date < today
 *   - critical : 0–7 days
 *   - warning  : 8–30 days
 *   - upcoming : 31–60 days
 * Anything further out is ignored — too much noise.
 */
class ComplianceTracker
{
    public const BUCKETS = ['expired', 'critical', 'warning', 'upcoming'];

    /** Per-row shape: ['kind','label','doc','entity_id','entity_label','expiry','days','bucket','url'] */
    public function expiring(int $horizonDays = 60): array
    {
        $db    = \Config\Database::connect();
        $today = date('Y-m-d');
        $limit = date('Y-m-d', strtotime("+$horizonDays days"));
        $out   = [];

        // Drivers — license expiry
        $drivers = $db->table('drivers')
            ->select('id, driver_name, license_no, license_expiry')
            ->where('license_expiry IS NOT NULL', null, false)
            ->where('license_expiry <=', $limit)
            ->where('status', 1)
            ->orderBy('license_expiry', 'ASC')
            ->get()->getResultArray();
        foreach ($drivers as $d) {
            $out[] = $this->row(
                kind:        'driver',
                label:       'License',
                doc:         (string) ($d['license_no'] ?? ''),
                entityId:    (int) $d['id'],
                entityLabel: (string) ($d['driver_name'] ?? 'Driver #' . $d['id']),
                expiry:      (string) $d['license_expiry'],
                url:         site_url('drivers/' . (int) $d['id'] . '/edit'),
                today:       $today,
            );
        }

        // Vehicles — every expiry column
        $cols   = array_keys(\App\Models\VehicleModel::EXPIRY_FIELDS);
        $select = 'id, vehicle_number, ' . implode(', ', $cols);
        $where  = '(' . implode(' OR ', array_map(fn($c) => "$c IS NOT NULL AND $c <= '$limit'", $cols)) . ')';
        $rows   = $db->table('vehicles')->select($select)->where($where, null, false)->where('status', 1)->get()->getResultArray();
        foreach ($rows as $v) {
            foreach (\App\Models\VehicleModel::EXPIRY_FIELDS as $col => $label) {
                if (empty($v[$col]) || $v[$col] > $limit) continue;
                $out[] = $this->row(
                    kind:        'vehicle',
                    label:       $label,
                    doc:         '',
                    entityId:    (int) $v['id'],
                    entityLabel: (string) $v['vehicle_number'],
                    expiry:      (string) $v[$col],
                    url:         site_url('vehicles/' . (int) $v['id'] . '/edit'),
                    today:       $today,
                );
            }
        }

        usort($out, fn($a, $b) => strcmp($a['expiry'], $b['expiry']));
        return $out;
    }

    /** Convenience: group by bucket, with counts and rows. */
    public function summary(int $horizonDays = 60): array
    {
        $rows = $this->expiring($horizonDays);
        $by   = array_fill_keys(self::BUCKETS, []);
        foreach ($rows as $r) $by[$r['bucket']][] = $r;
        return [
            'rows'     => $rows,
            'by'       => $by,
            'counts'   => array_map('count', $by),
            'total'    => count($rows),
        ];
    }

    private function row(string $kind, string $label, string $doc, int $entityId, string $entityLabel, string $expiry, string $url, string $today): array
    {
        $days   = (int) floor((strtotime($expiry) - strtotime($today)) / 86400);
        $bucket = $days < 0   ? 'expired'
              : ($days <= 7   ? 'critical'
              : ($days <= 30  ? 'warning'
              :                 'upcoming'));
        return [
            'kind'         => $kind,
            'label'        => $label,
            'doc'          => $doc,
            'entity_id'    => $entityId,
            'entity_label' => $entityLabel,
            'expiry'       => $expiry,
            'days'         => $days,
            'bucket'       => $bucket,
            'url'          => $url,
        ];
    }
}
