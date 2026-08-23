<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Domain\Ai\Knowledge\ArabicNormalizer;
use App\Models\WaConversationAiState;

/**
 * Compliance checks run BEFORE the bot generates a reply.
 * Handles: opt-out, disclosure, regulated advice detection, abuse de-escalation.
 */
final class ComplianceService
{
    // Customer opt-out keywords — any message containing these pauses the bot
    private const OPT_OUT_KEYWORDS = [
        'إيقاف', 'ايقاف', 'الغاء', 'إلغاء', 'توقف', 'stop', 'unsubscribe',
        'لا أريد', 'ما أبي', 'اوقف', 'أوقف',
    ];

    // Regulated topics that must be escalated — no LLM answer.
    // Multi-word phrases are matched as substrings; single-word tokens marked
    // with /* advice_only */ require an adjacent question or advice verb before
    // triggering (RC5 fix: prevents false positives from seller ad text like "24 صك").
    private const REGULATED_PHRASES = [
        'تمويل عقاري',  // mortgage
        'قرض عقاري',
        'رهن عقاري',
        'نسبة الفائدة',
        'الفائدة البنكية',
        'اشتراط البنك',
        'نزاع',
        'قضية',
        'محكمة',
        'توثيق شرعي',
        'تفريغ الصك',   // deed transfer formalities
        'بنك التمويل',
        'قسط بنكي',     // bank instalment — implies financing product advice
    ];

    // Short tokens that are regulated ONLY when paired with an advice/question context.
    // "كفيل" in a question context IS regulated; standalone "صك" in property
    // listings is NOT — "مساحه بالصك 123م" simply means the deed-registered area,
    // which is standard listing language. Deed-transfer formalities are handled by
    // the phrase "تفريغ الصك" in REGULATED_PHRASES above.
    private const REGULATED_ADVICE_ONLY = [
        'كفيل',  // guarantor (financing/guarantee contract advice)
        'نزاع',  // legal dispute (also in REGULATED_PHRASES; kept here for advice-only logic)
    ];

    // Payment-plan / instalment keywords — route to search_knowledge before escalating
    private const PAYMENT_PLAN_KEYWORDS = [
        'دفعات', 'دفع شهري', 'تقسيط', 'قسط', 'أقساط', 'بالتقسيط', 'خطة دفع',
    ];

    // Abuse / profanity triggers — escalate immediately
    private const ABUSE_TRIGGERS = [
        'احمق', 'غبي', 'تافه', 'كذاب', 'نصاب', 'حرامي',
    ];

    /**
     * Check inbound message for compliance issues.
     *
     * @return array{action: 'proceed'|'opt_out'|'handoff'|'disclosure', reason?: string, message?: string}
     */
    public function check(
        string $inboundText,
        WaConversationAiState $aiState,
        bool $isFirstContact,
        bool $discloseEnabled = true,
    ): array {
        $normalized = ArabicNormalizer::normalizeForSearch($inboundText);

        // 1. Opt-out detection
        foreach (self::OPT_OUT_KEYWORDS as $kw) {
            if (str_contains($normalized, ArabicNormalizer::normalizeForSearch($kw))) {
                return [
                    'action'  => 'opt_out',
                    'reason'  => 'customer_opt_out',
                    'message' => 'تم تسجيل طلبك. لن نرسل لك ردوداً تلقائية. يمكنك التواصل مع فريقنا مباشرةً في أي وقت.',
                ];
            }
        }

        // 2. Abuse detection — escalate silently
        foreach (self::ABUSE_TRIGGERS as $trigger) {
            if (str_contains($normalized, ArabicNormalizer::normalizeForSearch($trigger))) {
                return [
                    'action' => 'handoff',
                    'reason' => 'abusive_message',
                    'message'=> 'نأسف على هذه التجربة. سيتواصل معك أحد موظفينا مباشرة.',
                ];
            }
        }

        // 3. Regulated topics — check both with and without definite article prefix
        $normalizedStripped = ArabicNormalizer::stripDefiniteArticle($normalized);
        foreach (self::REGULATED_PHRASES as $phrase) {
            $normPhrase        = ArabicNormalizer::normalizeForSearch($phrase);
            $normPhraseStripped = ArabicNormalizer::stripDefiniteArticle($normPhrase);
            if (
                str_contains($normalized, $normPhrase) ||
                str_contains($normalizedStripped, $normPhraseStripped)
            ) {
                return [
                    'action' => 'handoff',
                    'reason' => 'regulated_topic:' . $phrase,
                    'message'=> 'هذا الموضوع يحتاج متخصص. سأحوّلك لأحد فريقنا الآن.',
                ];
            }
        }

        // 3b. Short regulated tokens — only trigger when paired with advice/question context (RC5).
        // Seller ad text like "عدد 24 صك" must NOT trigger; "كيف أحوّل الصك؟" MUST.
        $advicePattern = '/[؟?]|ينفع|يصير|أقدر|كيف|هل |استفسار|نصيحة|ممكن |يجوز|ما\s+هو|ماهو|طريقة|خطوات/u';
        if (preg_match($advicePattern, $inboundText)) {
            foreach (self::REGULATED_ADVICE_ONLY as $token) {
                $normToken = ArabicNormalizer::normalizeForSearch($token);
                if (str_contains($normalized, $normToken)) {
                    return [
                        'action' => 'handoff',
                        'reason' => 'regulated_topic:' . $token,
                        'message'=> 'هذا الموضوع يحتاج متخصص. سأحوّلك لأحد فريقنا الآن.',
                    ];
                }
            }
        }

        // 3c. Payment-plan questions — signal to search_knowledge first, not escalate.
        // Return a special 'proceed_with_kb' action so Employee routes to knowledge search.
        foreach (self::PAYMENT_PLAN_KEYWORDS as $kw) {
            if (str_contains($normalized, ArabicNormalizer::normalizeForSearch($kw))) {
                return ['action' => 'proceed_with_kb', 'reason' => 'payment_plan'];
            }
        }

        // 4. First-contact disclosure — only when enabled in WaAiConfig and this is
        //    truly the first bot reply (not mid-conversation after slot-fill).
        if (
            $discloseEnabled
            && $isFirstContact
            && ! $aiState->disclosed_as_assistant
        ) {
            return ['action' => 'disclosure'];
        }

        return ['action' => 'proceed'];
    }

    public function buildDisclosurePrefix(string $assistantName): string
    {
        return "مرحباً! أنا *{$assistantName}*، المساعد الرقمي. سأحاول مساعدتك بأسرع وقت. إذا أردت التحدث مع شخص مباشرة، فقط قل \"تحدث مع موظف\".\n\n";
    }

    public function isOptOutRequest(string $text): bool
    {
        $normalized = ArabicNormalizer::normalizeForSearch($text);
        foreach (self::OPT_OUT_KEYWORDS as $kw) {
            if (str_contains($normalized, ArabicNormalizer::normalizeForSearch($kw))) {
                return true;
            }
        }
        return false;
    }

    public function isHumanRequestKeyword(string $text): bool
    {
        $normalized = ArabicNormalizer::normalizeForSearch($text);
        $humanKeywords = [
            'تحدث مع موظف', 'تكلم موظف', 'موظف حقيقي', 'شخص حقيقي', 'بشري',
            'تكلم مع شخص', 'تحدث مع شخص', 'أريد موظف', 'ابي موظف',
            'المسؤول', 'human', 'agent', 'speak to someone', 'real person',
            // "مدير" alone is too broad ("مدير فرع يبغى غرفة" = customer's manager, not handoff)
            'كلم المدير', 'تحدث مع المدير', 'ابي المدير', 'أريد المدير', 'ابغى المدير',
        ];
        foreach ($humanKeywords as $kw) {
            if (str_contains($normalized, ArabicNormalizer::normalizeForSearch($kw))) {
                return true;
            }
        }
        return false;
    }
}
