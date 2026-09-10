<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\CityModel;

/**
 * Sanity-check the CityModel resolver — the autocomplete + soft FK both rely
 * on its prefix-priority and alias matching.
 */
final class CitiesLookupTest extends CIUnitTestCase
{
    public function testSearchReturnsPrefixMatchesFirst(): void
    {
        $rows = (new CityModel())->search('mum', 5);
        $this->assertNotEmpty($rows, 'No cities matched "mum" — has the seed been run?');
        $this->assertSame('Mumbai', $rows[0]['name']);
    }

    public function testResolveIsCaseInsensitive(): void
    {
        $m = new CityModel();
        $this->assertSame('Mumbai', $m->resolve('mumbai')['name'] ?? null);
        $this->assertSame('Mumbai', $m->resolve('MUMBAI')['name'] ?? null);
    }

    public function testResolveFollowsAliases(): void
    {
        $hit = (new CityModel())->resolve('Bombay');
        $this->assertNotNull($hit, '"Bombay" should resolve to Mumbai via alias');
        $this->assertSame('Mumbai', $hit['name']);
    }

    public function testResolveReturnsNullForUnknown(): void
    {
        $this->assertNull((new CityModel())->resolve('NoSuchCity42'));
    }
}
