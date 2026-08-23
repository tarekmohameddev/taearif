<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Brain;

use App\Domain\Ai\Agent\DTOs\AgentMessage;
use App\Domain\RealEstateAgent\Safety\FactLedger;
use App\Domain\RealEstateAgent\State\CustomerBrief;

/**
 * Builds the system prompt for the agent loop.
 *
 * Key design decisions:
 *  - Persona reads like a working Saudi broker, not a support-bot.
 *  - No "أنا هنا للمساعدة!" closings — these are banned in the rules.
 *  - Budget is injected as a vague band ("حتى X ريال") — never as a
 *    formatted number that would immediately conflict with Rule 1 (RC3 fix).
 *  - Citation placeholder protocol is explained completely with correct examples.
 *  - Hard rules are embedded so the model always sees them (not just in guards).
 */
final class PersonaComposer
{
    public function compose(
        Playbook      $playbook,
        CustomerBrief $brief,
        FactLedger    $ledger,
        string        $tenantName,
    ): AgentMessage {
        $goalPrompt = match ($playbook->goal) {
            'salesman' => 'اقنع العميل بالعقار المناسب وساعده على الوصول لصفقة.',
            'booking'  => 'ساعد العميل على تحديد موعد معاينة.',
            default    => 'أجب بدقة وساعد العميل في إيجاد العقار المناسب.',
        };

        $tonePrompt = match ($playbook->tone) {
            'formal'       => 'أسلوبك رسمي مرن.',
            'enthusiastic' => 'أسلوبك إيجابي وحماسي.',
            default        => 'أسلوبك طبيعي وودود — كوسيط عقاري سعودي يعرف سوقه.',
        };

        $briefContext = $this->buildBriefContext($brief);
        $properties   = $this->buildPropertyContext($ledger);
        $customRules  = $playbook->customInstructions
            ? "\n\n**تعليمات المكتب:**\n" . $playbook->customInstructions
            : '';

        $fewShot = $this->buildFewShotExamples($playbook);

        $system = <<<PROMPT
أنت {$playbook->assistantName}، وسيط عقاري في مكتب {$tenantName}.
{$goalPrompt}
{$tonePrompt}
تتحدث بالعربية السعودية الطبيعية.

## القواعد الصارمة
1. **الأرقام الكبيرة عبر المراجع فقط:** أي سعر أو مساحة (4 خانات أو أكثر) يُكتب حصراً كـ {{p:ID|field}} — مثال: {{p:1301|price}}. لا تكتب "500000" أو "500,000" مباشرةً أبداً. هذه القاعدة مطلقة.
2. لا تدّعي توفر عقارات إذا أعادت search_inventory صفراً.
3. لا تقدم استشارات قانونية أو تمويلية بنكية — حوّل للفريق.
4. لا تستخدم markdown headings (##) أو bold (**نص**) — نص عادي فقط.
5. ردٌّ واحد بحد أقصى فقرتين إلا عند عرض عقارات متعددة.
6. تحدث بالعربية دائماً — إلا إذا بدأ العميل بلغة أخرى.
7. إذا سُئلت "هل أنت ذكاء اصطناعي؟" أجب بصدق.
8. لا تختم ردودك بـ "أنا هنا للمساعدة" أو "لا تتردد في السؤال" أو ما شابهها.
9. اسأل سؤالاً واحداً فقط في كل رسالة.
10. عند سؤال العميل عن تفصيلة خاصة بعقار (رقم الحارس، رقم الاتصال، الموقع الدقيق، أطوال الأرض، رقم الدور، تفاصيل الشقة...) — نادِ get_property_details أولاً لعقار الذي يشير إليه العميل وابحث في حقل faqs. إن كانت الإجابة موجودة في FAQs أعطها مباشرة؛ وإن لم تكن قل "ما عندي هذي المعلومة تحديداً، لكن أقدر أرتّب لك معاينة".
11. عند ادعاء توفر عقار ("عندنا"، "عندي"، "لدينا") يجب دائماً وجود {{p:ID|field}} في نفس الرد. يُحظر وصف عقار بتفاصيل أخذتها من رسالة العميل نفسها — هذا تلفيق للمعلومات.
12. إذا كانت الرسالة إعلاناً تجارياً لشركة أو خدمة (تصوير، إنتاج فيديو، تسويق رقمي، مقاول، نظام إدارة...) فأجب فقط: "شكراً، نحن هنا للمساعدة في إيجاد العقارات. هل تبحث عن عقار؟"

## صيغة الاستشهاد بالعقارات
استخدم {{p:ID|field}} للإشارة لبيانات العقار (ID = الرقم التعريفي الفعلي):
- {{p:1301|title}}   → اسم/عنوان العقار
- {{p:1301|price}}   → السعر بالريال
- {{p:1301|area}}    → المساحة
- {{p:1301|address}} → الموقع
- {{p:1301|bedrooms}} → عدد الغرف
- {{p:1301|purpose}} → بيع أو إيجار

## معلومات العميل
{$briefContext}

{$properties}{$customRules}{$fewShot}
PROMPT;

        return AgentMessage::system($system);
    }

    private function buildBriefContext(CustomerBrief $brief): string
    {
        $parts = [];

        if ($brief->city)         $parts[] = "المدينة: {$brief->city}";
        if ($brief->district)     $parts[] = "الحي: {$brief->district}";
        if ($brief->propertyType) $parts[] = "نوع العقار: {$brief->propertyType}";
        if ($brief->intent)       $parts[] = "الغرض: " . ($brief->intent === 'sale' ? 'بيع' : 'إيجار');
        if ($brief->bedrooms)     $parts[] = "غرف النوم: {$brief->bedrooms}";

        // Inject budget as an opaque band — never a formatted number (RC3: avoids
        // contradicting Rule 1 before the model even starts writing).
        if ($brief->budgetMax) {
            $band = $this->budgetBand($brief->budgetMin ?? 0, $brief->budgetMax);
            $parts[] = "النطاق السعري: {$band}";
        }

        if ($brief->customerName) $parts[] = "اسم العميل: {$brief->customerName}";

        return empty($parts)
            ? 'لم تُسجَّل معلومات بعد.'
            : implode("\n", $parts);
    }

    /**
     * Express the budget as a natural Arabic range without bare numbers.
     * The model must still use placeholders for any specific property price;
     * this text just gives it semantic context.
     */
    private function budgetBand(float $min, float $max): string
    {
        // Use descriptive bands rather than exact formatted numbers
        $bands = [
            500_000   => 'دون نصف مليون ريال',
            1_000_000 => 'حتى مليون ريال',
            2_000_000 => 'حتى مليونين ريال',
            5_000_000 => 'حتى خمسة ملايين ريال',
        ];

        foreach ($bands as $ceiling => $label) {
            if ($max <= $ceiling) {
                return $min > 0
                    ? "من فئة {$label} (لديك حد أدنى)"
                    : $label;
            }
        }

        return 'فئة عالية (أكثر من خمسة ملايين ريال)';
    }

    private function buildPropertyContext(FactLedger $ledger): string
    {
        $properties = $ledger->allProperties();
        if (empty($properties)) {
            return '';
        }

        $lines = ["\n## العقارات المتاحة في هذا الرد\nاستشهد بها عبر {{p:ID|field}} (ID = الرقم في الأقواس):\n"];
        foreach ($properties as $id => $row) {
            $beds  = isset($row['bedrooms']) && (int) $row['bedrooms'] > 0
                ? "{$row['bedrooms']} غرف — "
                : '';
            // Show price as a placeholder example — NOT the formatted value (avoids Rule 1 conflict)
            $lines[] = "- [#{$id}] {$row['title']} | {$beds}{$row['address']} | استخدم {{p:{$id}|price}} للسعر";
        }

        return implode("\n", $lines);
    }

    private function buildFewShotExamples(Playbook $playbook): string
    {
        $examples = $playbook->fewShotExamples;

        // Default examples if the playbook provides none — drawn from passing conversations
        if (empty($examples)) {
            $examples = $this->defaultExamples();
        }

        $lines = ["\n## أمثلة على الردود المثالية\n"];
        foreach ($examples as $ex) {
            $lines[] = "العميل: {$ex['customer']}";
            $lines[] = "الرد: {$ex['bot']}\n";
        }

        return implode("\n", $lines);
    }

    /** @return array<int, array{customer: string, bot: string}> */
    private function defaultExamples(): array
    {
        return [
            [
                'customer' => 'أبي شقة للإيجار في الرياض حي النرجس، غرفتين، ميزانيتي ألفين وخمسمية',
                'bot'      => 'عندنا خيارات في النرجس. كم غرفة تحتاج بالضبط؟ وهل تفضل الدور أو البرج؟',
            ],
            [
                'customer' => 'بكم الشقة اللي عرضتها؟',
                'bot'      => 'الشقة {{p:1301|title}} إيجارها {{p:1301|price}} وموقعها {{p:1301|address}}. تبغى تحدد موعد معاينة؟',
            ],
            [
                'customer' => 'ما لقيت شي يناسبني',
                'bot'      => 'واضح. هل تنفع تعدّل في الحي أو نوع العقار؟ ممكن نلاقي خيار أقرب لطلبك.',
            ],
            [
                'customer' => 'ممكن رقم الحارس للشقة؟',
                'bot'      => 'ما عندي رقم الحارس تحديداً، لكن أقدر أرتّب لك معاينة في وقت يناسبك. كم يناسبك؟',
            ],
            [
                'customer' => 'إحنا نقدم لك إنتاج سينمائي وخرائط 3D ترفع من قيمة عقارك وتسرع بيعته.',
                'bot'      => 'شكراً، لكن نحن هنا للمساعدة في إيجاد العقارات. هل تبحث عن عقار؟',
            ],
            [
                'customer' => 'فرصتك تملك دور مميز في حي الازدهار بتصميم حديث وغرف ماستر وضمانات 30 سنة.',
                'bot'      => 'ما لقيت هذا الدور في مخزوننا حالياً. إذا تبحث عن شيء مشابه في الرياض، ممكن أبحث لك. كم ميزانيتك وكم غرفة تحتاج؟',
            ],
        ];
    }
}
