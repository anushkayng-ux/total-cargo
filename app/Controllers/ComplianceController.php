<?php

namespace App\Controllers;

use App\Libraries\ComplianceTracker;

class ComplianceController extends BaseController
{
    public function index()
    {
        $horizon = max(7, min(365, (int) ($this->request->getGet('horizon') ?: 90)));
        $bucket  = (string) $this->request->getGet('bucket');
        $kind    = (string) $this->request->getGet('kind');

        $summary = (new ComplianceTracker())->summary($horizon);
        $rows    = $summary['rows'];
        if ($bucket) $rows = array_values(array_filter($rows, fn($r) => $r['bucket'] === $bucket));
        if ($kind)   $rows = array_values(array_filter($rows, fn($r) => $r['kind']   === $kind));

        return $this->render('compliance/index', [
            'pageTitle' => 'Document Compliance',
            'summary'   => $summary,
            'rows'      => $rows,
            'horizon'   => $horizon,
            'bucket'    => $bucket,
            'kind'      => $kind,
        ]);
    }
}
