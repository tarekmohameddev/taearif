<?php

declare(strict_types=1);

namespace App\Domain\Calling\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * Writes to the Asterisk MariaDB realtime tables (ps_endpoints, ps_auths, ps_aors).
 *
 * Uses the 'asterisk' DB connection exclusively.
 * Asterisk 20 with PJSIP realtime loads changes immediately — no dialplan reload needed.
 *
 * Column sets match the Asterisk 20 alembic schema already migrated on the PBX.
 */
final class AsteriskRealtimeRepository
{
    private const CONNECTION = 'asterisk';

    // -----------------------------------------------------------------
    // Agent (WebRTC softphone) provisioning
    // -----------------------------------------------------------------

    public function upsertAgent(
        string $sipUsername,
        string $sipPassword,
        string $context,
        string $callerIdName,
        string $callerIdExt
    ): void {
        $db = DB::connection(self::CONNECTION);

        $db->table('ps_auths')->upsert(
            [
                'id'        => $sipUsername,
                'auth_type' => 'userpass',
                'username'  => $sipUsername,
                'password'  => $sipPassword,
            ],
            ['id'],
            ['auth_type', 'username', 'password']
        );

        $db->table('ps_aors')->upsert(
            [
                'id'              => $sipUsername,
                'max_contacts'    => 1,
                'remove_existing' => 'yes',
                'qualify_frequency' => 30,
            ],
            ['id'],
            ['max_contacts', 'remove_existing', 'qualify_frequency']
        );

        $db->table('ps_endpoints')->upsert(
            [
                'id'                     => $sipUsername,
                'transport'              => 'transport-wss',
                'aors'                   => $sipUsername,
                'auth'                   => $sipUsername,
                'context'                => $context,
                'disallow'               => 'all',
                'allow'                  => 'opus,ulaw,alaw',
                'webrtc'                 => 'yes',
                'dtls_auto_generate_cert' => 'yes',
                'direct_media'           => 'no',
                'force_rport'            => 'yes',
                'rewrite_contact'        => 'yes',
                'rtp_symmetric'          => 'yes',
                'callerid'               => "\"{$callerIdName}\" <{$callerIdExt}>",
            ],
            ['id'],
            [
                'transport', 'aors', 'auth', 'context',
                'disallow', 'allow', 'webrtc', 'dtls_auto_generate_cert',
                'direct_media', 'force_rport', 'rewrite_contact', 'rtp_symmetric', 'callerid',
            ]
        );
    }

    public function deleteAgent(string $sipUsername): void
    {
        $db = DB::connection(self::CONNECTION);
        $db->table('ps_endpoints')->where('id', $sipUsername)->delete();
        $db->table('ps_auths')->where('id', $sipUsername)->delete();
        $db->table('ps_aors')->where('id', $sipUsername)->delete();
    }

    // -----------------------------------------------------------------
    // Trunk (Yeastar GSM or STC SIP) provisioning
    // -----------------------------------------------------------------

    public function upsertTrunk(
        string $endpointId,
        string $password,
        string $context,
        string $transport = 'transport-udp,transport-tls'
    ): void {
        $db = DB::connection(self::CONNECTION);

        $db->table('ps_auths')->upsert(
            [
                'id'        => $endpointId,
                'auth_type' => 'userpass',
                'username'  => $endpointId,
                'password'  => $password,
            ],
            ['id'],
            ['auth_type', 'username', 'password']
        );

        $db->table('ps_aors')->upsert(
            [
                'id'              => $endpointId,
                'max_contacts'    => 1,
                'remove_existing' => 'yes',
                'qualify_frequency' => 30,
            ],
            ['id'],
            ['max_contacts', 'remove_existing', 'qualify_frequency']
        );

        $db->table('ps_endpoints')->upsert(
            [
                'id'              => $endpointId,
                'transport'       => $transport,
                'aors'            => $endpointId,
                'auth'            => $endpointId,
                'context'         => $context,
                'disallow'        => 'all',
                'allow'           => 'ulaw,alaw',
                'direct_media'    => 'no',
                'force_rport'     => 'yes',
                'rewrite_contact' => 'yes',
                'rtp_symmetric'   => 'yes',
            ],
            ['id'],
            [
                'transport', 'aors', 'auth', 'context',
                'disallow', 'allow', 'direct_media',
                'force_rport', 'rewrite_contact', 'rtp_symmetric',
            ]
        );
    }

    public function deleteTrunk(string $endpointId): void
    {
        $this->deleteAgent($endpointId); // same three tables
    }

    // -----------------------------------------------------------------
    // Status queries
    // -----------------------------------------------------------------

    /**
     * Check if a PJSIP endpoint has at least one registered contact.
     * ps_contacts is populated by Asterisk when a SIP device registers.
     */
    public function isEndpointRegistered(string $endpointId): bool
    {
        return DB::connection(self::CONNECTION)
            ->table('ps_contacts')
            ->where('endpoint', $endpointId)
            ->where('expiration_time', '>', now()->timestamp)
            ->exists();
    }
}
