<?php

namespace App\Services;

use App\Models\SipAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SipProvisioningService
{
    /**
     * Provision a SIP account into Asterisk realtime tables.
     */
    public function provision(SipAccount $sip): void
    {
        DB::transaction(function () use ($sip) {
            $id = $sip->username;

            // ps_endpoints
            DB::table('ps_endpoints')->updateOrInsert(
                ['id' => $id],
                [
                    'transport' => 'transport-udp',
                    'aors' => $id,
                    'auth' => in_array($sip->auth_type, ['password', 'both']) ? $id : null,
                    'context' => 'from-internal',
                    'disallow' => 'all',
                    'allow' => $sip->codec_allow,
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
    }

    /**
     * Generate a secure random SIP password.
     */
    public static function generatePassword(int $length = 20): string
    {
        return Str::random($length);
    }
}
