<?php

namespace App\Console\Commands;

use App\Services\Agi\AgiConnection;
use App\Services\Agi\CallEndHandler;
use App\Services\Agi\InboundCallHandler;
use App\Services\Agi\OutboundCallHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AgiServer extends Command
{
    protected $signature = 'agi:serve {--host=0.0.0.0} {--port=4573}';

    protected $description = 'Start the FastAGI routing server for Asterisk';

    public function handle(): int
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $address = "tcp://{$host}:{$port}";

        $server = @stream_socket_server($address, $errno, $errstr);

        if (!$server) {
            $this->error("Failed to start AGI server on {$address}: {$errstr} ({$errno})");
            return self::FAILURE;
        }

        $this->info("FastAGI server listening on {$address}");
        Log::info("FastAGI server started on {$address}");

        stream_set_blocking($server, false);

        $running = true;

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, function () use (&$running) {
                $running = false;
            });
            pcntl_signal(SIGINT, function () use (&$running) {
                $running = false;
            });
        }

        while ($running) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            $read = [$server];
            $write = null;
            $except = null;

            if (stream_select($read, $write, $except, 1) > 0) {
                $client = @stream_socket_accept($server, 5);

                if ($client) {
                    $this->handleConnection($client);
                }
            }
        }

        fclose($server);
        $this->info('FastAGI server stopped.');

        return self::SUCCESS;
    }

    private function handleConnection($socket): void
    {
        try {
            $agi = new AgiConnection($socket);
            $agi->parse();

            $script = $agi->getScript();

            Log::debug("AGI request: {$script}", [
                'channel' => $agi->getEnv('channel'),
                'extension' => $agi->getEnv('extension'),
                'callerid' => $agi->getEnv('callerid'),
            ]);

            match ($script) {
                'route_outbound' => app(OutboundCallHandler::class)->handle($agi),
                'route_inbound' => app(InboundCallHandler::class)->handle($agi),
                'call_end' => app(CallEndHandler::class)->handle($agi),
                default => $agi->verbose("rSwitch: Unknown AGI script: {$script}", 1),
            };
        } catch (\Throwable $e) {
            Log::error("AGI error: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }
}
