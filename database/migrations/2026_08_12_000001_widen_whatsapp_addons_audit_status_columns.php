<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marker appended to rows whose status values were destroyed by the enum
     * mismatch before this migration ran.
     */
    private const LOST_MARKER = '[status values lost: number statuses did not fit the pending/approved/rejected enum]';

    /**
     * Run the migrations.
     *
     * whatsapp_addons_audit started life as an addon-only audit table, so
     * old_status/new_status were ENUM('pending','approved','rejected').
     * 2025_12_25_100000 made it polymorphic (entity_type addon|number) without
     * widening those enums, so WhatsappNumberController::toggleStatus() — which
     * writes whatsapp_users statuses ('active','inactive','blocked','not_linked')
     * — cannot store a valid value: it truncates to '' on permissive servers and
     * throws (rolling back the status change itself) under STRICT_TRANS_TABLES.
     *
     * The two entity types have disjoint status vocabularies, so no single enum
     * is correct for the column; a union enum would still allow nonsense pairs
     * (an addon row storing 'blocked') and would need another migration every
     * time either vocabulary changes — which is exactly the drift that caused
     * this bug. Plain VARCHAR fits the polymorphic table and matches existing
     * string status columns elsewhere in the schema.
     */
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_addons_audit')) {
            return;
        }

        if ($this->isEnum('old_status')) {
            DB::statement('ALTER TABLE whatsapp_addons_audit MODIFY old_status VARCHAR(32) NULL');
        }

        if ($this->isEnum('new_status')) {
            DB::statement('ALTER TABLE whatsapp_addons_audit MODIFY new_status VARCHAR(32) NOT NULL');
        }

        // Flag rows written while the enums were too narrow. Their original
        // values are genuinely unrecoverable: the toggle is binary but nothing
        // in the row records the direction, and whatsapp_users.status is also
        // written outside this controller, so walking the history backwards from
        // the current status would fabricate an audit trail rather than restore
        // one. Mark the gap explicitly instead of guessing.
        DB::statement(
            "UPDATE whatsapp_addons_audit
                SET note = TRIM(CONCAT(IFNULL(note, ''), ' ', ?)),
                    old_status = NULL,
                    new_status = 'unknown'
              WHERE entity_type = 'number'
                AND (old_status = '' OR new_status = '')",
            [self::LOST_MARKER]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_addons_audit')) {
            return;
        }

        $allowed = ['pending', 'approved', 'rejected'];

        $nonConforming = DB::table('whatsapp_addons_audit')
            ->where(function ($query) use ($allowed) {
                $query->whereNotNull('old_status')->whereNotIn('old_status', $allowed);
            })
            ->orWhereNotIn('new_status', $allowed)
            ->count();

        // Narrowing back to the enum would silently blank these rows. Refuse
        // rather than destroy audit history; the operator can delete or export
        // the number-entity rows first if the rollback is genuinely wanted.
        if ($nonConforming > 0) {
            throw new RuntimeException(
                "Cannot revert whatsapp_addons_audit status columns to ENUM: {$nonConforming} row(s) hold "
                . 'values outside pending/approved/rejected (number-entity audits). '
                . 'Export or remove those rows first.'
            );
        }

        DB::statement("ALTER TABLE whatsapp_addons_audit MODIFY old_status ENUM('pending','approved','rejected') NULL");
        DB::statement("ALTER TABLE whatsapp_addons_audit MODIFY new_status ENUM('pending','approved','rejected') NOT NULL");
    }

    /**
     * Whether the column is still declared as an ENUM, so up() stays idempotent.
     */
    private function isEnum(string $column): bool
    {
        $row = DB::selectOne(
            'SELECT DATA_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['whatsapp_addons_audit', $column]
        );

        return $row !== null && strtolower($row->DATA_TYPE) === 'enum';
    }
};
