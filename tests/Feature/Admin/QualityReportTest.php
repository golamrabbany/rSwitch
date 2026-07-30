<?php

namespace Tests\Feature\Admin;

use PHPUnit\Framework\TestCase;

/**
 * Source-level guards on the quality report.
 *
 * There is no local database, so these assert on the controller source the same
 * way AudioHealthFilterTest does. They defend three properties that are silent
 * when broken: partition pruning, tenant isolation, and not surfacing a column
 * known to be wrong.
 */
class QualityReportTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(__DIR__ . '/../../../app/Http/Controllers/Admin/OperationalReportController.php');
    }

    private function method(): string
    {
        $src = $this->source();
        $start = strpos($src, 'public function qualityReport');
        $this->assertNotFalse($start, 'qualityReport() not found');
        $end = strpos($src, "\n    public function ", $start + 10);
        return substr($src, $start, $end === false ? null : $end - $start);
    }

    public function test_every_query_goes_through_the_scope_closure(): void
    {
        $m = $this->method();

        // The $scope closure is what applies BOTH the call_start bound (so the
        // daily partitions prune) and the tenant filter. So the invariant worth
        // asserting is not "call_start appears N times" -- it appears once,
        // inside the closure -- but that no CallRecord query bypasses $scope.
        $this->assertStringContainsString('call_start', $m, 'the scope closure must bound call_start');

        $queries = substr_count($m, 'CallRecord::query()');
        $scoped  = substr_count($m, '$scope(CallRecord::query())');

        $this->assertGreaterThan(0, $queries, 'expected at least one CallRecord query');
        $this->assertSame(
            $queries,
            $scoped,
            'every CallRecord::query() must be wrapped in $scope() — an unscoped query '
            . 'both scans every partition and leaks other tenantsrows'
        );
    }

    public function test_non_super_admin_is_scoped_to_their_hierarchy(): void
    {
        $m = $this->method();
        $this->assertStringContainsString('isSuperAdmin', $m);
        $this->assertStringContainsString('descendantIds', $m);
    }

    public function test_range_is_capped(): void
    {
        $this->assertStringContainsString('31', $this->method(), 'the 31-day cap must be present');
    }

    public function test_unreliable_column_is_not_surfaced(): void
    {
        $this->assertStringNotContainsString('rtp_cust_tx_count', $this->method());
    }
}
