<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration B — financial 10x credit rescale.
 *
 * This migration MUST be run deliberately in a maintenance window.
 * It preserves every tenant's purchasing power by multiplying all credit
 * balances / package credits by 10 at the same time as the per-message
 * costs increase 10x (so Marketing goes from 1 → 10 credits/msg).
 *
 * Fully reversible — down() divides everything by 10.
 */
return new class extends Migration
{
    private const SCALE = 10;

    public function up(): void
    {
        DB::transaction(function (): void {
            // ── 1. Rescale pricing rows ───────────────────────────────────────
            // credits_per_message × 10, price_per_credit ÷ 10 (effective unchanged)
            DB::statement('
                UPDATE marketing_channel_pricing
                SET
                    credits_per_message        = credits_per_message * ' . self::SCALE . ',
                    price_per_credit           = price_per_credit    / ' . self::SCALE . ',
                    effective_price_per_message = credits_per_message * ' . self::SCALE . ' * (price_per_credit / ' . self::SCALE . ')
                WHERE is_billable = 1 AND credits_per_message > 0
            ');

            // ── 2. Rescale credit packages ───────────────────────────────────
            DB::statement('
                UPDATE credit_packages
                SET credits = credits * ' . self::SCALE . '
            ');

            // ── 3. Rescale user_credits balances ─────────────────────────────
            DB::statement('
                UPDATE user_credits
                SET
                    total_credits    = total_credits    * ' . self::SCALE . ',
                    used_credits     = used_credits     * ' . self::SCALE . ',
                    reserved_credits = reserved_credits * ' . self::SCALE . '
            ');

            // ── 4. Rescale non-terminal wa_campaigns ─────────────────────────
            // status in (scheduled, in_progress, paused) — any reserved credits
            // and the credits_per_message snapshot in meta need to stay balanced.
            $activeCampaigns = DB::table('wa_campaigns')
                ->whereIn('status', ['scheduled', 'in_progress', 'paused'])
                ->select('id', 'reserved_credits', 'meta')
                ->get();

            foreach ($activeCampaigns as $campaign) {
                $meta = $campaign->meta ? json_decode($campaign->meta, true) : [];
                if (isset($meta['credits_per_message']) && is_numeric($meta['credits_per_message'])) {
                    $meta['credits_per_message'] = (int) $meta['credits_per_message'] * self::SCALE;
                }

                DB::table('wa_campaigns')->where('id', $campaign->id)->update([
                    'reserved_credits' => $campaign->reserved_credits * self::SCALE,
                    'meta'             => json_encode($meta),
                ]);
            }

            // ── 5. Write one audit CreditTransaction per affected user ───────
            $affectedUsers = DB::table('user_credits')->select('user_id')->get();
            $now = now();

            foreach ($affectedUsers as $row) {
                DB::table('credit_transactions')->insert([
                    'user_id'          => $row->user_id,
                    'transaction_type' => 'adjustment',
                    'credits_amount'   => 0,
                    'description'      => 'Automatic 10× credit-unit rescale: Marketing = 10 cr/msg, Utility = 2, Auth = 2, AI Bot = 1, Service = 0. Balance preserved.',
                    'metadata'         => json_encode([
                        'type'       => 'system_rescale',
                        'reference'  => 'rescale_10x_' . $now->format('YmdHis'),
                        'scale'      => self::SCALE,
                    ]),
                    'status'           => 'completed',
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            // ── 1. Reverse pricing rows ───────────────────────────────────────
            DB::statement('
                UPDATE marketing_channel_pricing
                SET
                    credits_per_message        = credits_per_message / ' . self::SCALE . ',
                    price_per_credit           = price_per_credit    * ' . self::SCALE . ',
                    effective_price_per_message = (credits_per_message / ' . self::SCALE . ') * (price_per_credit * ' . self::SCALE . ')
                WHERE is_billable = 1 AND credits_per_message > 0
            ');

            // ── 2. Reverse credit packages ────────────────────────────────────
            DB::statement('
                UPDATE credit_packages
                SET credits = credits / ' . self::SCALE . '
            ');

            // ── 3. Reverse user_credits balances ──────────────────────────────
            DB::statement('
                UPDATE user_credits
                SET
                    total_credits    = total_credits    / ' . self::SCALE . ',
                    used_credits     = used_credits     / ' . self::SCALE . ',
                    reserved_credits = reserved_credits / ' . self::SCALE . '
            ');

            // ── 4. Reverse non-terminal wa_campaigns ──────────────────────────
            $activeCampaigns = DB::table('wa_campaigns')
                ->whereIn('status', ['scheduled', 'in_progress', 'paused'])
                ->select('id', 'reserved_credits', 'meta')
                ->get();

            foreach ($activeCampaigns as $campaign) {
                $meta = $campaign->meta ? json_decode($campaign->meta, true) : [];
                if (isset($meta['credits_per_message']) && is_numeric($meta['credits_per_message'])) {
                    $meta['credits_per_message'] = (int) ($meta['credits_per_message'] / self::SCALE);
                }

                DB::table('wa_campaigns')->where('id', $campaign->id)->update([
                    'reserved_credits' => (int) ($campaign->reserved_credits / self::SCALE),
                    'meta'             => json_encode($meta),
                ]);
            }

            // Remove the rescale audit transactions
            DB::table('credit_transactions')
                ->where('transaction_type', 'adjustment')
                ->where('metadata->type', 'system_rescale')
                ->delete();
        });
    }
};
