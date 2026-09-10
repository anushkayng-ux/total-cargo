<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\AuditLogger;

/**
 * Audit log writes shouldn't break — but they also shouldn't bubble exceptions
 * back to the caller. Verify both: a normal call inserts a row and a call
 * with a deliberately-bad payload returns silently.
 */
final class AuditLoggerTest extends CIUnitTestCase
{
    public function testNormalCallWritesRow(): void
    {
        $db = \Config\Database::connect();
        $before = $db->table('activity_logs')->countAllResults();
        AuditLogger::log('test_module', 1, 'test_action', 'Smoke test row', ['x' => 1], ['x' => 2]);
        $after = $db->table('activity_logs')->countAllResults();
        $this->assertSame($before + 1, $after);
        // Clean up
        $db->table('activity_logs')->where('module_name', 'test_module')->delete();
    }

    public function testBadPayloadDoesNotThrow(): void
    {
        // Pass a circular-ish payload by referencing a closure (not JSON-encodable).
        // The logger should swallow the failure rather than crash the caller.
        $this->expectNotToPerformAssertions();
        AuditLogger::log('test_module', 1, 'test_action', null, ['fn' => fn() => 1], null);
    }
}
