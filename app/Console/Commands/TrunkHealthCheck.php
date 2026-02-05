<?php

namespace App\Console\Commands;

use App\Models\Trunk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TrunkHealthCheck extends Command
{
    protected $signature = 'trunk:health-check';

    protected $description = 'Check health of trunks via AMI SIP OPTIONS and auto-disable failing trunks';

    public function handle(): int
    {
        $trunks = Trunk::where('health_check', true)
            ->whereIn('status', ['active', 'auto_disabled'])
            ->get();

        if ($trunks->isEmpty()) {
            $this->info('No trunks with health checking enabled.');
            return self::SUCCESS;
        }

        $this->info("Checking {$trunks->count()} trunk(s)...");

        $ami = $this->connectAmi();

        if (!$ami) {
            $this->error('Could not connect to AMI. Skipping health checks.');
            return self::FAILURE;
        }

        foreach ($trunks as $trunk) {
            $this->checkTrunk($ami, $trunk);
        }

        $this->disconnectAmi($ami);

        $this->info('Trunk health check complete.');

        return self::SUCCESS;
    }

    private function checkTrunk($ami, Trunk $trunk): void
    {
        $endpoint = "trunk-{$trunk->direction}-{$trunk->id}";

        // Send qualify (SIP OPTIONS) via AMI
        $isUp = $this->qualifyEndpoint($ami, $endpoint);

        $previousStatus = $trunk->health_status;

        if ($isUp) {
            $trunk->health_status = 'up';
            $trunk->health_last_up_at = now();
            $trunk->health_fail_count = 0;

            // Auto-re-enable if it was auto-disabled
            if ($trunk->status === 'auto_disabled') {
                $trunk->status = 'active';
                $this->warn("  [{$trunk->name}] UP — auto-re-enabled");
                Log::info("Trunk auto-re-enabled", ['trunk' => $trunk->id, 'name' => $trunk->name]);
            } else {
                $this->line("  [{$trunk->name}] UP");
            }
        } else {
            $trunk->health_fail_count = ($trunk->health_fail_count ?? 0) + 1;
            $trunk->health_status = 'down';

            // Auto-disable if threshold exceeded
            $threshold = $trunk->health_auto_disable_threshold ?? 5;

            if ($threshold > 0 && $trunk->health_fail_count >= $threshold && $trunk->status === 'active') {
                $trunk->status = 'auto_disabled';
                $this->error("  [{$trunk->name}] DOWN (fail #{$trunk->health_fail_count}) — AUTO-DISABLED");
                Log::warning("Trunk auto-disabled", [
                    'trunk' => $trunk->id,
                    'name' => $trunk->name,
                    'fail_count' => $trunk->health_fail_count,
                ]);
            } else {
                $this->warn("  [{$trunk->name}] DOWN (fail #{$trunk->health_fail_count})");
            }
        }

        $trunk->health_last_checked_at = now();
        $trunk->save();
    }

    private function qualifyEndpoint($ami, string $endpoint): bool
    {
        $command = "Action: PJSIPQualify\r\nEndpoint: {$endpoint}\r\n\r\n";
        fwrite($ami, $command);

        $response = $this->readAmiResponse($ami);

        // PJSIPQualify returns Response: Success if the endpoint exists
        // and the qualify request was sent. Then we get an event with status.
        if (stripos($response, 'Success') !== false) {
            // Wait briefly for the qualify event response
            usleep(500000); // 500ms

            // Check if there's a response event
            $event = $this->readAmiNonBlocking($ami);

            // If we got no response or it contains "Unreachable", it's down
            if (stripos($event, 'Unreachable') !== false) {
                return false;
            }

            // "Reachable" or successful qualify = up
            return true;
        }

        // Endpoint doesn't exist or error
        return false;
    }

    private function connectAmi()
    {
        $host = config('services.ami.host', env('AMI_HOST', 'asterisk'));
        $port = (int) config('services.ami.port', env('AMI_PORT', 5038));
        $username = config('services.ami.username', env('AMI_USERNAME', 'laravel'));
        $secret = config('services.ami.secret', env('AMI_SECRET', ''));

        $fp = @fsockopen($host, $port, $errno, $errstr, 5);

        if (!$fp) {
            Log::error("AMI connection failed: {$errstr} ({$errno})");
            return null;
        }

        stream_set_timeout($fp, 5);

        // Read banner
        fgets($fp, 1024);

        // Login
        fwrite($fp, "Action: Login\r\nUsername: {$username}\r\nSecret: {$secret}\r\n\r\n");
        $loginResponse = $this->readAmiResponse($fp);

        if (stripos($loginResponse, 'Success') === false) {
            fclose($fp);
            Log::error('AMI login failed: ' . trim($loginResponse));
            return null;
        }

        return $fp;
    }

    private function disconnectAmi($fp): void
    {
        fwrite($fp, "Action: Logoff\r\n\r\n");
        fclose($fp);
    }

    private function readAmiResponse($fp): string
    {
        $response = '';
        $timeout = time() + 5;

        while (!feof($fp) && time() < $timeout) {
            $line = fgets($fp, 1024);
            if ($line === false) {
                break;
            }
            $response .= $line;
            if (trim($line) === '') {
                break;
            }
        }

        return $response;
    }

    private function readAmiNonBlocking($fp): string
    {
        $response = '';
        stream_set_timeout($fp, 1);

        while (!feof($fp)) {
            $line = fgets($fp, 1024);
            if ($line === false) {
                break;
            }
            $response .= $line;
            if (trim($line) === '') {
                break;
            }
        }

        stream_set_timeout($fp, 5);

        return $response;
    }
}
