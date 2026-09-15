<?php

namespace App\Controllers;

/** Lane-level profitability report. Gated by feature_flag 'lane_profitability'. */
class LaneProfitabilityController extends BaseController
{
    public function index()
    {
        $req  = $this->request;
        $from = (string) $req->getGet('from');
        $to   = (string) $req->getGet('to');
        if ($from === '') $from = date('Y-m-d', strtotime('-90 days'));
        if ($to === '')   $to   = date('Y-m-d');

        $db = \Config\Database::connect();
        // Lanes derived from booking route_text — naive split on common delimiters
        $sql = "
            SELECT
              TRIM(SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(REPLACE(b.route_text,'->','→'),' to ','→'),'—','→'),'-','→'), '→', 1)) AS pickup,
              TRIM(SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(REPLACE(b.route_text,'->','→'),' to ','→'),'—','→'),'-','→'), '→', -1)) AS drop_city,
              COUNT(b.id) AS trips,
              ROUND(SUM(b.final_sell_rate),  2) AS revenue,
              ROUND(SUM(b.final_buy_rate),   2) AS buy_cost,
              ROUND(SUM(b.margin_amount),    2) AS margin,
              ROUND(IF(SUM(b.final_sell_rate) > 0, SUM(b.margin_amount) * 100 / SUM(b.final_sell_rate), 0), 1) AS margin_pct
            FROM bookings b
           WHERE b.deleted_at IS NULL
             AND b.booking_status IN ('Approved','Handed Over','Completed')
             AND b.loading_date BETWEEN ? AND ?
             AND b.route_text IS NOT NULL AND b.route_text != ''
           GROUP BY pickup, drop_city
          HAVING trips > 0
           ORDER BY margin DESC
           LIMIT 200
        ";
        $rows = $db->query($sql, [$from, $to])->getResultArray();

        return $this->render('reports/lane_profitability', [
            'pageTitle' => 'Lane Profitability [Reports]',
            'rows'      => $rows,
            'from'      => $from,
            'to'        => $to,
        ], retroFixedShell: true);
    }
}
