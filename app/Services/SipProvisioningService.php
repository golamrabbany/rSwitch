<?php

namespace App\Services;

use App\Models\SipAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SipProvisioningService
{
    /**
     * Default codec for all endpoints.
     * Using a single codec eliminates transcoding overhead (~2x capacity gain).
     * ulaw (G.711 µ-law) is chosen for widest compatibility and zero CPU transcoding.
     */
    public const DEFAULT_CODEC = 'ulaw';
    /**
     * Provision a SIP account into Asterisk realtime tables.
     */
    public function provision(SipAccount $sip, bool $skipReload = false): void
    {
        DB::transaction(function () use ($sip) {
            $id = $sip->username;

            // Force single codec to prevent transcoding (biggest performance gain)
            $codec = self::DEFAULT_CODEC;

            // ps_endpoints
            DB::table('ps_endpoints')->updateOrInsert(
                ['id' => $id],
                [
                    'transport' => 'transport-udp',
                    'aors' => $id,
                    'auth' => in_array($sip->auth_type, ['password', 'both']) ? $id : null,
                    'context' => 'from-internal',
                    'disallow' => 'all',
                    'allow' => $codec,
                    'direct_media' => 'no',
                    'rtp_symmetric' => 'yes',
                    'force_rport' => 'yes',
                    'rewrite_contact' => 'yes',
                    'callerid' => $sip->caller_id_name
                        ? "\"{$sip->caller_id_name}\" <{$sip->caller_id_number}>"
                        : $sip->caller_id_number,
                    'device_state_busy_at' => $sip->max_channels,
                ],
            );

            // ps_auths (only for password or both auth types)
            if (in_array($sip->auth_type, ['password', 'both'])) {
                DB::table('ps_auths')->updateOrInsert(
                    ['id' => $id],
                    [
                        'auth_type' => 'userpass',
                        'username' => $id,
                        'password' => $sip->password,
                    ],
                );
            } else {
                DB::table('ps_auths')->where('id', $id)->delete();
            }

            // ps_aors
            DB::table('ps_aors')->updateOrInsert(
                ['id' => $id],
                [
                    'max_contacts' => 1,
                    'qualify_frequency' => 60,
                    'remove_existing' => 'yes',
                ],
            );

            // ps_endpoint_id_ips (for IP auth)
            DB::table('ps_endpoint_id_ips')->where('endpoint', $id)->delete();

            if (in_array($sip->auth_type, ['ip', 'both']) && $sip->allowed_ips) {
                $ips = array_filter(array_map('trim', explode(',', $sip->allowed_ips)));
                foreach ($ips as $ip) {
                    DB::table('ps_endpoint_id_ips')->insert([
                        'endpoint' => $id,
                        'match' => $ip,
                    ]);
                }
            }
        });

        // Invalidate Sorcery memory cache so Asterisk picks up the changes
        if (!$skipReload) {
            $this->reloadPjsip();
        }
    }

    /**
     * Remove a SIP account from Asterisk realtime tables.
     */
    public function deprovision(SipAccount $sip): void
    {
        $id = $sip->username;

        DB::transaction(function () use ($id) {
            DB::table('ps_endpoint_id_ips')->where('endpoint', $id)->delete();
            DB::table('ps_contacts')->where('id', 'like', "{$id};%")->delete();
            DB::table('ps_aors')->where('id', $id)->delete();
            DB::table('ps_auths')->where('id', $id)->delete();
            DB::table('ps_endpoints')->where('id', $id)->delete();
        });

        // Invalidate Sorcery memory cache
        $this->reloadPjsip();
    }

    /**
     * Reload PJSIP module to invalidate Sorcery memory cache.
     * This forces Asterisk to re-read endpoints from the database
     * on next access, ensuring provisioning changes take effect immediately.
     */
    public function reloadPjsip(): void
    {
        try {
            $asteriskHost = config('services.ami.host', '127.0.0.1');
            $amiPort = (int) config('services.ami.port', 5038);
            $amiUser = config('services.ami.username', 'rswitch');
            $amiSecret = config('services.ami.secret', '');

            // Connect to Asterisk AMI and send reload command
            $socket = @fsockopen($asteriskHost, $amiPort, $errno, $errstr, 3);

            if (!$socket) {
                Log::warning("SipProvisioning: AMI connect failed ({$errstr})");
                return;
            }

            stream_set_timeout($socket, 3);

            // Read banner
            fgets($socket);

            // Login
            $loginCmd = "Action: Login\r\nUsername: {$amiUser}\r\nSecret: {$amiSecret}\r\n\r\n";
            @fwrite($socket, $loginCmd);
            $this->readAmiResponse($socket);

            // Send PJSIP reload to flush sorcery cache
            @fwrite($socket, "Action: Command\r\nCommand: pjsip reload\r\n\r\n");
            $this->readAmiResponse($socket);

            // Logoff
            @fwrite($socket, "Action: Logoff\r\n\r\n");
            @fclose($socket);
        } catch (\Throwable $e) {
            Log::warning("SipProvisioning: PJSIP reload failed - {$e->getMessage()}");
        }
    }

    /**
     * Read an AMI response until empty line.
     */
    private function readAmiResponse($socket): string
    {
        $response = '';
        stream_set_timeout($socket, 3);
        while ($line = fgets($socket)) {
            $response .= $line;
            if (trim($line) === '') {
                break;
            }
        }
        return $response;
    }

    /**
     * Generate a secure random SIP password.
     */
    public static function generatePassword(int $length = 20): string
    {
        return Str::random($length);
    }
}
