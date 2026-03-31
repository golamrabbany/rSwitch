<?php

namespace App\Services;

use App\Models\Trunk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrunkProvisioningService
{
    /**
     * Provision all active trunks to Asterisk realtime DB tables.
     * Same approach as SipProvisioningService — no config files needed.
     */
    public function provisionAll(): void
    {
        $trunks = Trunk::where('status', 'active')->get();
        $activeIds = [];

        foreach ($trunks as $trunk) {
            $this->provision($trunk);
            $activeIds[] = $this->trunkId($trunk);
        }

        // Remove deprovisioned trunks (no longer active)
        $this->cleanupInactiveTrunks($activeIds);

        try {
            $this->reloadPjsip();
        } catch (\Exception $e) {
            Log::warning('PJSIP reload failed after trunk provisioning', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Provision a single trunk into Asterisk realtime tables.
     */
    public function provision(Trunk $trunk): void
    {
        $id = $this->trunkId($trunk);
        $isOutgoing = in_array($trunk->direction, ['outgoing', 'both']);
        $hasAuth = !empty($trunk->username) && !empty($trunk->password);

        DB::transaction(function () use ($trunk, $id, $isOutgoing, $hasAuth) {
            // ps_endpoints
            $endpoint = [
                'transport' => 'transport-' . ($trunk->transport ?: 'udp'),
                'context' => $trunk->incoming_context ?: 'from-trunk',
                'disallow' => 'all',
                'allow' => $trunk->codec_allow ?: 'ulaw,alaw,g729',
                'direct_media' => 'no',
                'rtp_symmetric' => 'yes',
                'force_rport' => 'yes',
                'rewrite_contact' => 'yes',
            ];

            if ($hasAuth && $isOutgoing) {
                $endpoint['outbound_auth'] = "{$id}-auth";
            }
            if ($isOutgoing) {
                $endpoint['aors'] = "{$id}-aor";
            }

            DB::table('ps_endpoints')->updateOrInsert(['id' => $id], $endpoint);

            // ps_auths (only if credentials set)
            if ($hasAuth) {
                DB::table('ps_auths')->updateOrInsert(
                    ['id' => "{$id}-auth"],
                    [
                        'auth_type' => 'userpass',
                        'username' => $trunk->username,
                        'password' => $trunk->password,
                    ]
                );
            } else {
                DB::table('ps_auths')->where('id', "{$id}-auth")->delete();
            }

            // ps_aors (only for outgoing/both)
            if ($isOutgoing) {
                $port = $trunk->port ?: 5060;
                DB::table('ps_aors')->updateOrInsert(
                    ['id' => "{$id}-aor"],
                    [
                        'contact' => "sip:{$trunk->host}:{$port}",
                        'qualify_frequency' => 60,
                    ]
                );
            } else {
                DB::table('ps_aors')->where('id', "{$id}-aor")->delete();
            }

            // ps_endpoint_id_ips (match incoming packets from trunk host)
            DB::table('ps_endpoint_id_ips')->where('endpoint', $id)->delete();

            $matchIps = [$trunk->host];
            if (!empty($trunk->incoming_ip_acl)) {
                $extraIps = array_filter(array_map('trim', explode(',', $trunk->incoming_ip_acl)));
                $matchIps = array_unique(array_merge($matchIps, $extraIps));
            }

            foreach ($matchIps as $ip) {
                DB::table('ps_endpoint_id_ips')->insert([
                    'endpoint' => $id,
                    'match' => $ip,
                ]);
            }
        });

        Log::info("Trunk provisioned: {$trunk->name} ({$id})");
    }

    /**
     * Deprovision a trunk from Asterisk realtime tables.
     */
    public function deprovision(Trunk $trunk): void
    {
        $id = $this->trunkId($trunk);

        DB::transaction(function () use ($id) {
            DB::table('ps_endpoint_id_ips')->where('endpoint', $id)->delete();
            DB::table('ps_contacts')->where('id', 'like', "{$id}%")->delete();
            DB::table('ps_aors')->where('id', "{$id}-aor")->delete();
            DB::table('ps_auths')->where('id', "{$id}-auth")->delete();
            DB::table('ps_endpoints')->where('id', $id)->delete();
        });

        Log::info("Trunk deprovisioned: {$id}");

        try {
            $this->reloadPjsip();
        } catch (\Exception $e) {
            Log::warning('PJSIP reload failed after trunk deprovision', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove trunk entries from realtime tables that are no longer active.
     */
    protected function cleanupInactiveTrunks(array $activeIds): void
    {
        $allTrunkEndpoints = DB::table('ps_endpoints')
            ->where('id', 'like', 'trunk-%')
            ->pluck('id')
            ->toArray();

        $stale = array_diff($allTrunkEndpoints, $activeIds);

        foreach ($stale as $id) {
            DB::table('ps_endpoint_id_ips')->where('endpoint', $id)->delete();
            DB::table('ps_contacts')->where('id', 'like', "{$id}%")->delete();
            DB::table('ps_aors')->where('id', "{$id}-aor")->delete();
            DB::table('ps_auths')->where('id', "{$id}-auth")->delete();
            DB::table('ps_endpoints')->where('id', $id)->delete();
            Log::info("Stale trunk removed: {$id}");
        }
    }

    /**
     * Generate the trunk endpoint ID.
     */
    protected function trunkId(Trunk $trunk): string
    {
        return "trunk-{$trunk->direction}-{$trunk->id}";
    }

    /**
     * Reload PJSIP in Asterisk via AMI.
     */
    protected function reloadPjsip(): bool
    {
        $host = config('services.ami.host', '127.0.0.1');
        $port = (int) config('services.ami.port', 5038);
        $username = config('services.ami.username', 'rswitch');
        $secret = config('services.ami.secret', '');

        $fp = @fsockopen($host, $port, $errno, $errstr, 5);

        if (!$fp) {
            throw new \RuntimeException("AMI connection failed: {$errstr} ({$errno})");
        }

        stream_set_timeout($fp, 5);
        fgets($fp, 1024); // banner

        @fwrite($fp, "Action: Login\r\nUsername: {$username}\r\nSecret: {$secret}\r\n\r\n");
        $loginResponse = $this->readAmiResponse($fp);

        if (stripos($loginResponse, 'Success') === false) {
            fclose($fp);
            throw new \RuntimeException('AMI login failed');
        }

        @fwrite($fp, "Action: Command\r\nCommand: pjsip reload\r\n\r\n");
        $this->readAmiResponse($fp);

        @fwrite($fp, "Action: Logoff\r\n\r\n");
        @fclose($fp);

        Log::info('PJSIP reloaded via AMI');

        return true;
    }

    /**
     * Read AMI response.
     */
    protected function readAmiResponse($fp): string
    {
        $response = '';
        $timeout = time() + 5;

        while (!feof($fp) && time() < $timeout) {
            $line = fgets($fp, 1024);
            if ($line === false) break;
            $response .= $line;
            if (trim($line) === '') break;
        }

        return $response;
    }
}
