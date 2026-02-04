<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // call_records requires monthly RANGE partitioning — must use raw SQL
        DB::statement("
            CREATE TABLE call_records (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                uuid VARCHAR(36) NOT NULL,
                sip_account_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                reseller_id BIGINT UNSIGNED NULL,
                call_flow ENUM('sip_to_trunk','sip_to_sip','trunk_to_sip','trunk_to_trunk') NOT NULL,
                caller VARCHAR(40) NOT NULL,
                callee VARCHAR(40) NOT NULL,
                caller_id VARCHAR(80) NULL,
                src_ip VARCHAR(45) NULL,
                dst_ip VARCHAR(45) NULL,
                incoming_trunk_id BIGINT UNSIGNED NULL,
                outgoing_trunk_id BIGINT UNSIGNED NULL,
                did_id BIGINT UNSIGNED NULL,
                destination VARCHAR(100) NOT NULL DEFAULT '',
                matched_prefix VARCHAR(20) NOT NULL DEFAULT '',
                rate_per_minute DECIMAL(10,6) NOT NULL DEFAULT 0,
                connection_fee DECIMAL(10,6) NOT NULL DEFAULT 0,
                rate_group_id BIGINT UNSIGNED NULL,
                call_start DATETIME NOT NULL,
                call_end DATETIME NULL,
                duration INT UNSIGNED NOT NULL DEFAULT 0,
                billsec INT UNSIGNED NOT NULL DEFAULT 0,
                billable_duration INT UNSIGNED NOT NULL DEFAULT 0,
                total_cost DECIMAL(10,4) NOT NULL DEFAULT 0,
                reseller_cost DECIMAL(10,4) NOT NULL DEFAULT 0,
                disposition ENUM('ANSWERED','NO ANSWER','BUSY','FAILED','CANCEL') NULL,
                hangup_cause VARCHAR(50) NULL,
                status ENUM('in_progress','rated','failed','unbillable') NOT NULL DEFAULT 'in_progress',
                ast_channel VARCHAR(80) NULL,
                ast_dstchannel VARCHAR(80) NULL,
                ast_context VARCHAR(40) NULL,
                rated_at TIMESTAMP NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id, call_start),
                UNIQUE KEY idx_uuid (uuid, call_start),
                KEY idx_user_date (user_id, call_start),
                KEY idx_reseller_date (reseller_id, call_start),
                KEY idx_status (status, call_start),
                KEY idx_sip_account (sip_account_id, call_start),
                KEY idx_caller (caller, call_start),
                KEY idx_callee (callee, call_start)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            PARTITION BY RANGE (TO_DAYS(call_start)) (
                PARTITION p2026_01 VALUES LESS THAN (TO_DAYS('2026-02-01')),
                PARTITION p2026_02 VALUES LESS THAN (TO_DAYS('2026-03-01')),
                PARTITION p2026_03 VALUES LESS THAN (TO_DAYS('2026-04-01')),
                PARTITION p2026_04 VALUES LESS THAN (TO_DAYS('2026-05-01')),
                PARTITION p2026_05 VALUES LESS THAN (TO_DAYS('2026-06-01')),
                PARTITION p2026_06 VALUES LESS THAN (TO_DAYS('2026-07-01')),
                PARTITION p2026_07 VALUES LESS THAN (TO_DAYS('2026-08-01')),
                PARTITION p2026_08 VALUES LESS THAN (TO_DAYS('2026-09-01')),
                PARTITION p2026_09 VALUES LESS THAN (TO_DAYS('2026-10-01')),
                PARTITION p2026_10 VALUES LESS THAN (TO_DAYS('2026-11-01')),
                PARTITION p2026_11 VALUES LESS THAN (TO_DAYS('2026-12-01')),
                PARTITION p2027_01 VALUES LESS THAN (TO_DAYS('2027-02-01')),
                PARTITION p_future VALUES LESS THAN MAXVALUE
            )
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS call_records');
    }
};
