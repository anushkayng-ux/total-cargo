<?php

namespace App\Controllers;

use App\Libraries\Analytics;

class VendorScoringController extends BaseController
{
    public function index()
    {
        return $this->render('vendors/scoring', [
            'pageTitle' => 'Vendor Scorecard [Reports]',
            'rows'      => Analytics::vendorScorecards(),
        ], retroFixedShell: true);
    }
}
