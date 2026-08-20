<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Domain\Ai\Knowledge\ArabicNormalizer;

/**
 * Deterministic relevance gate — runs BEFORE any LLM call to drop traffic that
 * is clearly off-topic or spam, based on patterns mined from the 128k corpus.
 *
 * Rule priority (first match wins):
 *  1. Empty or near-empty message → off_topic
 *  2. Real-estate inquiry keywords → relevant
 *  3. Off-topic keyword patterns (maintenance, receipts, marketing blasts) → off_topic
 *  4. Default → relevant (conservative; ambiguous messages proceed to the LLM)
 */
final class RelevanceGate
{
    private const MIN_CHAR_LENGTH = 2;

    // Phrases that strongly indicate a real-estate inquiry
    private const REAL_ESTATE_KEYWORDS = [
        'عقار', 'شقة', 'شقه', 'فيلا', 'فله', 'أرض', 'ارض',
        'غرفة', 'غرف', 'إيجار', 'ايجار', 'بيع', 'شراء', 'للبيع',
        'سعر', 'ميزانية', 'ريال', 'مساحة', 'متر',
        'مكتب', 'محل', 'مستودع', 'دور', 'ملحق',
        'استراحة', 'شاليه', 'روف', 'فرش', 'مفروش',
        'حي', 'حيّ', 'منطقة', 'الحي',
        'زيارة', 'جولة', 'معاينة', 'موعد',
        'rent', 'villa', 'apartment', 'property',
    ];

    // Off-topic patterns from corpus analysis (maintenance, admin, marketing)
    private const OFF_TOPIC_KEYWORDS = [
        'صيانة', 'تسريب', 'كهرباء', 'ماء', 'انقطاع',
        'رصيد', 'فاتورة', 'دفع الإيجار', 'تجديد العقد',
        'وصل', 'ايصال', 'إيصال',
        'إخلاء', 'اخلاء', 'تسليم مفاتيح', 'المفاتيح',
        'عروض رمضان', 'تهانينا', 'مبارك', 'عيد',
        'بروشور', 'كتالوج', 'صور فقط',
        'دعايا', 'تسويق', 'اشتراك',
    ];

    /**
     * Check whether the message is relevant enough to proceed.
     *
     * @return array{relevant: bool, reason: string}
     */
    public function check(string $messageText): array
    {
        $text = trim($messageText);

        if (mb_strlen($text) < self::MIN_CHAR_LENGTH) {
            return ['relevant' => false, 'reason' => 'too_short'];
        }

        $normalized = ArabicNormalizer::normalizeForSearch($text);

        // Real-estate keywords win immediately
        foreach (self::REAL_ESTATE_KEYWORDS as $kw) {
            if (str_contains($normalized, ArabicNormalizer::normalizeForSearch($kw))) {
                return ['relevant' => true, 'reason' => 'real_estate_keyword'];
            }
        }

        // Off-topic patterns
        foreach (self::OFF_TOPIC_KEYWORDS as $kw) {
            if (str_contains($normalized, ArabicNormalizer::normalizeForSearch($kw))) {
                return ['relevant' => false, 'reason' => 'off_topic:' . $kw];
            }
        }

        // Pure numeric / greeting-only → probably off-topic burst or greeting
        if (preg_match('/^[\d\s\p{P}]+$/u', $text)) {
            return ['relevant' => false, 'reason' => 'numeric_only'];
        }

        // Default: let the LLM handle ambiguous messages
        return ['relevant' => true, 'reason' => 'default_allow'];
    }
}
