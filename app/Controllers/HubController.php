<?php

namespace App\Controllers;

/**
 * Retro-demo style "hub" landing pages — Transportation / Accounts /
 * Administration. Each just card-links to the real existing routes
 * (grouped by tpt_hub_buckets()), so the sidebar can collapse to the
 * demo's 7 items without any real page becoming unreachable.
 */
class HubController extends BaseController
{
    public function transportation()
    {
        $buckets = tpt_hub_buckets($this->auth);
        return $this->render('hub/show', [
            'pageTitle' => 'Transportation',
            'heading'   => 'Transportation',
            'subtitle'  => 'Sales pipeline, bookings, trips, fleet & GPS',
            'icon'      => 'truck',
            'cards'     => $buckets['transportation'],
        ]);
    }

    public function accounts()
    {
        $buckets = tpt_hub_buckets($this->auth);
        return $this->render('hub/show', [
            'pageTitle' => 'Accounts',
            'heading'   => 'Accounts',
            'subtitle'  => 'Client invoices, receipts & vendor bills',
            'icon'      => 'cash-coin',
            'cards'     => $buckets['accounts'],
        ]);
    }

    public function administration()
    {
        $buckets = tpt_hub_buckets($this->auth);
        return $this->render('hub/show', [
            'pageTitle' => 'Administration',
            'heading'   => 'Administration',
            'subtitle'  => 'Reports, HR/payroll, communication & system setup',
            'icon'      => 'gear',
            'cards'     => $buckets['administration'],
        ]);
    }
}
