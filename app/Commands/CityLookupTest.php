<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\CityModel;

class CityLookupTest extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:city-test';
    protected $description = 'Smoke-test the city master autocomplete & resolver.';

    public function run(array $params)
    {
        $m = new CityModel();

        $total = $m->countAllResults();
        CLI::write("Total cities in master: $total", 'green');

        foreach (['mum', 'bang', 'jaipu', 'noida', 'koch'] as $q) {
            CLI::write("\nsearch('$q'):", 'cyan');
            foreach ($m->search($q, 5) as $r) {
                CLI::write("  • {$r['name']}, {$r['state']}  (Tier {$r['tier']})");
            }
        }

        CLI::write("\nresolve():", 'cyan');
        foreach (['Mumbai', 'mumbai', 'MUMBAI', 'Bombay', 'NoSuchCity'] as $name) {
            $hit = $m->resolve($name);
            CLI::write("  '$name' → " . ($hit ? "{$hit['name']}, {$hit['state']}" : 'not found'));
        }

        CLI::write("\nstatesList() count: " . count($m->statesList()), 'green');
    }
}
