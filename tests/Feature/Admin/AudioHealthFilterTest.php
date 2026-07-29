<?php

namespace Tests\Feature\Admin;

use PHPUnit\Framework\TestCase;

/**
 * Guards the audio-health filter's most important invariant by reading the
 * controller source directly, the same technique the Python engine tests use
 * for source-level assertions (see python-services/tests/test_leg_qos_handler.py).
 *
 * There is no local database/app to boot, so this deliberately extends the
 * plain PHPUnit\Framework\TestCase rather than the Laravel one -- no
 * RefreshDatabase, no HTTP request, just a regex over the controller file.
 *
 * Invariant: every audio-health filter arm must carry `billsec > 0`. Without
 * it, `no_audio` would match the ~74% of calls that never answer and
 * legitimately have no RTP, and the report would declare three quarters of
 * all traffic broken.
 */
class AudioHealthFilterTest extends TestCase
{
    private function controllerSource(): string
    {
        // No Laravel app is booted here (plain PHPUnit\Framework\TestCase), so
        // app_path() isn't available -- resolve the path relative to this file.
        return file_get_contents(
            __DIR__ . '/../../../app/Http/Controllers/Admin/OperationalReportController.php'
        );
    }

    private function matchArm(string $source, string $key): string
    {
        // Grabs from `'<key>' =>` up to the next top-level `'...' =>` arm or
        // the `default => null` line that closes the match().
        preg_match(
            "/'" . preg_quote($key, '/') . "'\s*=>(.+?)(?=\n\s*(?:'\w+'\s*=>|default\s*=>))/s",
            $source,
            $matches
        );

        $this->assertNotEmpty($matches, "Could not locate the '{$key}' arm of the audio filter match()");

        return $matches[1];
    }

    public function test_no_audio_arm_requires_billsec_guard(): void
    {
        $source = $this->controllerSource();
        $this->assertStringContainsString("'billsec', '>', 0", $this->matchArm($source, 'no_audio'));
    }

    public function test_one_way_arm_requires_billsec_guard(): void
    {
        $source = $this->controllerSource();
        $this->assertStringContainsString("'billsec', '>', 0", $this->matchArm($source, 'one_way'));
    }

    public function test_high_loss_arm_requires_billsec_guard(): void
    {
        $source = $this->controllerSource();
        $this->assertStringContainsString("'billsec', '>', 0", $this->matchArm($source, 'high_loss'));
    }
}
