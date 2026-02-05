<?php

namespace App\Services\Agi;

class AgiConnection
{
    private array $env = [];
    private $socket;

    public function __construct($socket)
    {
        $this->socket = $socket;
        stream_set_timeout($this->socket, 10);
    }

    /**
     * Parse AGI environment variables sent by Asterisk.
     * Asterisk sends lines like "agi_key: value" terminated by a blank line.
     */
    public function parse(): void
    {
        while (($line = fgets($this->socket)) !== false) {
            $line = trim($line);

            if ($line === '') {
                break;
            }

            if (str_starts_with($line, 'agi_')) {
                [$key, $value] = explode(':', $line, 2);
                $this->env[substr($key, 4)] = trim($value);
            }
        }
    }

    /**
     * Get an AGI environment variable (without "agi_" prefix).
     */
    public function getEnv(string $key, ?string $default = null): ?string
    {
        return $this->env[$key] ?? $default;
    }

    /**
     * Get the requested AGI script name from agi_request.
     * "agi://laravel.test:4573/route_outbound" -> "route_outbound"
     */
    public function getScript(): string
    {
        $request = $this->env['request'] ?? '';
        $path = parse_url($request, PHP_URL_PATH) ?: '';

        return ltrim($path, '/');
    }

    /**
     * Send an AGI command and read the response.
     */
    public function command(string $cmd): string
    {
        fwrite($this->socket, $cmd . "\n");
        fflush($this->socket);

        $response = fgets($this->socket);

        return $response !== false ? trim($response) : '';
    }

    /**
     * SET VARIABLE on the Asterisk channel.
     */
    public function setVariable(string $name, string $value): void
    {
        $this->command("SET VARIABLE {$name} \"{$value}\"");
    }

    /**
     * GET VARIABLE from the Asterisk channel.
     * Returns null if the variable is not set.
     */
    public function getVariable(string $name): ?string
    {
        $response = $this->command("GET VARIABLE {$name}");

        // Response format: "200 result=1 (value)" or "200 result=0"
        if (preg_match('/result=1 \((.+)\)/', $response, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * EXEC an Asterisk application.
     */
    public function exec(string $application, string $args = ''): string
    {
        return $this->command("EXEC {$application} {$args}");
    }

    /**
     * Log a VERBOSE message to Asterisk CLI.
     */
    public function verbose(string $message, int $level = 1): void
    {
        $this->command("VERBOSE \"{$message}\" {$level}");
    }

    public function getChannel(): string
    {
        return $this->env['channel'] ?? '';
    }

    public function getExtension(): string
    {
        return $this->env['extension'] ?? '';
    }

    public function getCallerId(): string
    {
        return $this->env['callerid'] ?? '';
    }

    public function getCallerIdName(): string
    {
        return $this->env['calleridname'] ?? '';
    }

    public function getUniqueId(): string
    {
        return $this->env['uniqueid'] ?? '';
    }

    public function getContext(): string
    {
        return $this->env['context'] ?? '';
    }
}
