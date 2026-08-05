<?php

declare(strict_types=1);

namespace Tests\Unit\Bot;

use App\Domain\Communication\WhatsApp\Bot\MessageFactExtractor;
use Tests\TestCase;

final class MessageFactExtractorTest extends TestCase
{
    // ─── Budget extraction ────────────────────────────────────────────────────

    public function test_it_extracts_million_budget(): void
    {
        $facts = MessageFactExtractor::extract(['بدور على شقة ميزانيتي 7 مليون']);
        $this->assertEquals(7_000_000.0, $facts['budget_max']);
    }

    public function test_it_extracts_million_and_half_budget(): void
    {
        $facts = MessageFactExtractor::extract(['ميزانيتي مليون ونص']);
        $this->assertEquals(1_500_000.0, $facts['budget_max']);
    }

    public function test_it_extracts_2_5_million_budget(): void
    {
        $facts = MessageFactExtractor::extract(['بحدود 2.5 مليون']);
        $this->assertEquals(2_500_000.0, $facts['budget_max']);
    }

    public function test_it_extracts_thousand_budget(): void
    {
        $facts = MessageFactExtractor::extract(['ميزانيتي 800 ألف']);
        $this->assertEquals(800_000.0, $facts['budget_max']);
    }

    public function test_it_extracts_range_budget(): void
    {
        $facts = MessageFactExtractor::extract(['بين 2 و 4 مليون']);
        $this->assertEquals(2_000_000.0, $facts['budget_min']);
        $this->assertEquals(4_000_000.0, $facts['budget_max']);
    }

    public function test_it_extracts_millionain_budget(): void
    {
        $facts = MessageFactExtractor::extract(['ميزانيتها مليونين']);
        $this->assertEquals(2_000_000.0, $facts['budget_max']);
    }

    public function test_it_extracts_millionain_and_half_budget(): void
    {
        $facts = MessageFactExtractor::extract(['ميزانيتها مليونين ونص']);
        $this->assertEquals(2_500_000.0, $facts['budget_max']);
    }

    public function test_it_extracts_less_than_million_budget(): void
    {
        $facts = MessageFactExtractor::extract(['لو اقل من مليون ياسلام']);
        $this->assertEquals(1_000_000.0, $facts['budget_max']);
    }

    public function test_it_extracts_budget_from_short_number_in_budget_context_as_thousands(): void
    {
        $facts = MessageFactExtractor::extract(['ميزانيتي حول 650']);
        $this->assertEquals(650_000.0, $facts['budget_max']);
    }

    public function test_it_extracts_monthly_rent_budget_range_without_thousands_multiplier(): void
    {
        $facts = MessageFactExtractor::extract(['بالشهري بحدود 2500-3000']);
        $this->assertEquals(2500.0, $facts['budget_min']);
        $this->assertEquals(3000.0, $facts['budget_max']);
    }

    public function test_it_prefers_price_after_saar_keyword_over_discount(): void
    {
        $facts = MessageFactExtractor::extract(['خصم 90 ألف السعر: 1,370,000']);
        $this->assertEquals(1_370_000.0, $facts['budget_max']);
    }

    public function test_it_does_not_extract_budget_from_urls(): void
    {
        $facts = MessageFactExtractor::extract(['https://sa.aqar.fm/ad/6623939/ar بسعر 26000.00 ريال']);
        $this->assertEquals(26_000.0, $facts['budget_max']);
    }

    public function test_it_extracts_raw_number_budget(): void
    {
        $facts = MessageFactExtractor::extract(['سعره 1500000']);
        $this->assertEquals(1_500_000.0, $facts['budget_max']);
    }

    public function test_it_does_not_extract_budget_when_absent(): void
    {
        $facts = MessageFactExtractor::extract(['أبغى شقة في الرياض']);
        $this->assertArrayNotHasKey('budget_max', $facts);
    }

    // ─── Property type extraction ─────────────────────────────────────────────

    public function test_it_extracts_apartment_type(): void
    {
        $facts = MessageFactExtractor::extract(['أبغى شقة في شمال الرياض']);
        $this->assertEquals('شقة', $facts['type']);
    }

    public function test_it_extracts_villa_type(): void
    {
        $facts = MessageFactExtractor::extract(['ابحث عن فيلا للبيع']);
        $this->assertEquals('فيلا', $facts['type']);
    }

    public function test_it_extracts_villa_from_falla_spelling(): void
    {
        $facts = MessageFactExtractor::extract(['عندك فله في حي النرجس؟']);
        $this->assertEquals('فيلا', $facts['type']);
    }

    public function test_it_extracts_villa_from_plural_fallal(): void
    {
        $facts = MessageFactExtractor::extract(['هل عندكم فلل أو قصر مصغر يكون مؤثث في الرياض']);
        $this->assertEquals('فيلا', $facts['type']);
        $this->assertEquals('الرياض', $facts['city']);
    }

    public function test_it_extracts_building_type(): void
    {
        $facts = MessageFactExtractor::extract(['بدور على عمارة في شارع الملك عبدالعزيز']);
        $this->assertEquals('عمارة', $facts['type']);
    }

    public function test_it_extracts_million_and_hundreds_budget(): void
    {
        $facts = MessageFactExtractor::extract(['إلى مليون و700']);
        $this->assertEquals(1_700_000.0, $facts['budget_max']);
    }

    public function test_it_extracts_budget_range_around_n_to_m_million(): void
    {
        $facts = MessageFactExtractor::extract(['ميزانيتي حول 4 إلى 6 مليون']);
        $this->assertEquals(4_000_000.0, $facts['budget_min']);
        $this->assertEquals(6_000_000.0, $facts['budget_max']);
    }

    public function test_it_does_not_treat_area_meters_as_budget(): void
    {
        $facts = MessageFactExtractor::extract(['3 غرف نوم ومجلس ومطبخ مساحة حوالي 140 متر']);
        $this->assertArrayNotHasKey('budget_max', $facts);
        $this->assertEquals(3, $facts['bedrooms']);
    }

    public function test_it_extracts_land_type(): void
    {
        $facts = MessageFactExtractor::extract(['أبغى أرض في الرياض']);
        $this->assertEquals('أرض', $facts['type']);
    }

    public function test_it_extracts_office_type(): void
    {
        $facts = MessageFactExtractor::extract(['أبحث عن مكتب للإيجار']);
        $this->assertEquals('مكتب', $facts['type']);
    }

    public function test_it_extracts_duplex_type(): void
    {
        $facts = MessageFactExtractor::extract(['عندكم دوبلكس في الرياض؟']);
        $this->assertEquals('دوبلكس', $facts['type']);
    }

    public function test_it_does_not_treat_badur_verb_as_floor_type(): void
    {
        $facts = MessageFactExtractor::extract(['لا والله بدور على عقار استثماري معايا 10 مليون ريال']);
        $this->assertArrayNotHasKey('type', $facts);
        $this->assertEquals(10_000_000.0, $facts['budget_max']);
    }

    public function test_it_extracts_whole_word_floor_type(): void
    {
        $facts = MessageFactExtractor::extract(['أبغى دور في الرياض']);
        $this->assertEquals('دور', $facts['type']);
    }

    // ─── Location extraction ──────────────────────────────────────────────────

    public function test_it_extracts_city_riyadh(): void
    {
        $facts = MessageFactExtractor::extract(['أبغى شقة في الرياض']);
        $this->assertEquals('الرياض', $facts['city']);
    }

    public function test_it_extracts_city_jeddah_alternate_spelling(): void
    {
        $facts = MessageFactExtractor::extract(['عندكم وحدات في جده؟']);
        $this->assertEquals('جدة', $facts['city']);
    }

    public function test_it_does_not_treat_khabbirni_as_khobar_city(): void
    {
        $facts = MessageFactExtractor::extract(['أو إذا عندكم عمارة استثمارية بنفس الميزانية للبيع خبرني']);
        $this->assertArrayNotHasKey('city', $facts);
        $this->assertEquals('عمارة', $facts['type']);
        $this->assertEquals('sale', $facts['intent']);
    }

    public function test_it_extracts_district_with_hai_prefix(): void
    {
        $facts = MessageFactExtractor::extract(['أبغى فيلا في حي النرجس']);
        $this->assertStringContainsString('النرجس', $facts['district']);
    }

    public function test_it_extracts_multi_word_district_with_hai_prefix(): void
    {
        $facts = MessageFactExtractor::extract(['أبغى شقة في حي الملك فهد']);
        $this->assertEquals('حي الملك فهد', $facts['district']);
    }

    public function test_it_does_not_truncate_district_on_waw_inside_name(): void
    {
        // Regression: "الروضة" used to become "الر" because stop-token و matched mid-word.
        $facts = MessageFactExtractor::extract([
            'بدور على شقة للايجار غرفتين وصالة في حي الروضة أو السلامة أو الفيصلية بجدة',
        ]);

        $this->assertEquals('حي الروضة', $facts['district']);
        $this->assertEquals('جدة', $facts['city']);
        $this->assertEquals('شقة', $facts['type']);
        $this->assertEquals(2, $facts['bedrooms']);
        $this->assertEquals('rent', $facts['intent']);
    }

    public function test_it_extracts_street_as_district(): void
    {
        $facts = MessageFactExtractor::extract(['بدور على عمارة في شارع الملك عبدالعزيز']);
        $this->assertStringContainsString('الملك عبدالعزيز', $facts['district']);
    }

    public function test_it_does_not_include_budget_words_in_street_district(): void
    {
        $facts = MessageFactExtractor::extract(['بدور على عمارة في شارع الملك عبد العزيز بميزانية 7 مليون']);

        $this->assertStringContainsString('الملك عبد العزيز', $facts['district']);
        $this->assertStringNotContainsString('بميزانية', $facts['district']);
        $this->assertStringNotContainsString('ميزانية', $facts['district']);
    }

    public function test_it_extracts_no_location_when_absent(): void
    {
        $facts = MessageFactExtractor::extract(['ابغى شقة بميزانية 5 مليون']);
        $this->assertArrayNotHasKey('city', $facts);
    }

    // ─── False-positive guards (follow-up questions) ─────────────────────────

    public function test_it_does_not_treat_unit_count_question_as_apartment_type(): void
    {
        $facts = MessageFactExtractor::extract(['كم شقة فيها وكم تقريباً الإيجار السنوي؟']);
        $this->assertArrayNotHasKey('type', $facts);
        $this->assertArrayNotHasKey('intent', $facts);
    }

    public function test_it_does_not_treat_office_location_question_as_office_type(): void
    {
        $facts = MessageFactExtractor::extract(['وين المكتب؟ أقدر أمر اليوم بعد المغرب؟']);
        $this->assertArrayNotHasKey('type', $facts);
    }

    public function test_it_still_extracts_office_when_searching_for_office_property(): void
    {
        $facts = MessageFactExtractor::extract(['أبحث عن مكتب للإيجار في الرياض']);
        $this->assertEquals('مكتب', $facts['type']);
        $this->assertEquals('rent', $facts['intent']);
    }

    // ─── Bedrooms extraction ─────────────────────────────────────────────────

    public function test_it_extracts_bedrooms_digit(): void
    {
        $facts = MessageFactExtractor::extract(['أبغى شقة 3 غرف في الرياض']);
        $this->assertEquals(3, $facts['bedrooms']);
    }

    public function test_it_extracts_bedrooms_with_arabic_indic_digits(): void
    {
        $facts = MessageFactExtractor::extract(['أبغى شقة ٣ غرف في الرياض']);
        $this->assertEquals(3, $facts['bedrooms']);
    }

    public function test_it_extracts_bedrooms_arabic_word(): void
    {
        $facts = MessageFactExtractor::extract(['أبغى فيلا ثلاث غرف']);
        $this->assertEquals(3, $facts['bedrooms']);
    }

    public function test_it_extracts_bedrooms_ghurfatain(): void
    {
        $facts = MessageFactExtractor::extract(['شقة غرفتين في جدة']);
        $this->assertEquals(2, $facts['bedrooms']);
    }

    // ─── Purpose extraction ───────────────────────────────────────────────────

    public function test_it_extracts_rent_purpose(): void
    {
        $facts = MessageFactExtractor::extract(['أبحث عن شقة للإيجار']);
        $this->assertEquals('rent', $facts['intent']);
    }

    public function test_it_extracts_sale_purpose(): void
    {
        $facts = MessageFactExtractor::extract(['أريد شراء فيلا']);
        $this->assertEquals('sale', $facts['intent']);
    }

    public function test_it_extracts_sale_from_lilbay_keyword(): void
    {
        $facts = MessageFactExtractor::extract(['عندكم فيلا للبيع؟']);
        $this->assertEquals('sale', $facts['intent']);
    }

    // ─── Multi-fact messages ──────────────────────────────────────────────────

    public function test_it_extracts_all_facts_from_rich_message(): void
    {
        $msg   = 'بدور على عمارة في شارع الملك عبدالعزيز وميزانيتي 7 مليون';
        $facts = MessageFactExtractor::extract([$msg]);

        $this->assertEquals('عمارة', $facts['type']);
        $this->assertStringContainsString('الملك عبدالعزيز', $facts['district']);
        $this->assertEquals(7_000_000.0, $facts['budget_max']);
    }

    // ─── hasSearchSignals ─────────────────────────────────────────────────────

    public function test_has_search_signals_returns_true_for_type(): void
    {
        $this->assertTrue(MessageFactExtractor::hasSearchSignals(['type' => 'شقة']));
    }

    public function test_has_search_signals_returns_true_for_budget(): void
    {
        $this->assertTrue(MessageFactExtractor::hasSearchSignals(['budget_max' => 1_000_000]));
    }

    public function test_has_search_signals_returns_false_for_location_only(): void
    {
        $this->assertFalse(MessageFactExtractor::hasSearchSignals(['city' => 'الرياض']));
    }

    public function test_has_search_signals_returns_false_for_empty_facts(): void
    {
        $this->assertFalse(MessageFactExtractor::hasSearchSignals([]));
    }

    // ─── Round-3 regressions ──────────────────────────────────────────────────

    public function test_ground_floor_apartment_is_not_land(): void
    {
        $facts = MessageFactExtractor::extract(['عندكم شقه للايجار ارضيه في البكيرية']);
        $this->assertSame('شقة', $facts['type'] ?? null);
        $this->assertSame('البكيرية', $facts['city'] ?? null);
        $this->assertSame('rent', $facts['intent'] ?? null);
    }

    public function test_indakum_does_not_trigger_unit_count_scrub(): void
    {
        // Regression: "كم" inside "عندكم" used to strip "شقه"
        $facts = MessageFactExtractor::extract(['عندكم شقه للايجار في جدة']);
        $this->assertSame('شقة', $facts['type'] ?? null);
    }

    public function test_it_extracts_qassim_cities(): void
    {
        $this->assertSame('بريدة', MessageFactExtractor::extract(['شقة شمال بريدة'])['city'] ?? null);
        $this->assertSame('عنيزة', MessageFactExtractor::extract(['غرفة مفروشة بعنيزة'])['city'] ?? null);
    }

    public function test_room_maps_to_apartment_type(): void
    {
        $facts = MessageFactExtractor::extract(['ابغى غرفة مفروشة بعنيزة']);
        $this->assertSame('شقة', $facts['type'] ?? null);
        $this->assertSame('عنيزة', $facts['city'] ?? null);
    }

    public function test_building_location_question_does_not_set_type(): void
    {
        $facts = MessageFactExtractor::extract(['ممكن موقع العمارة؟']);
        $this->assertArrayNotHasKey('type', $facts);
    }

    public function test_floor_number_question_does_not_set_type_or_bedrooms(): void
    {
        $facts = MessageFactExtractor::extract(['فيه دور رابع؟']);
        $this->assertArrayNotHasKey('type', $facts);
        $this->assertArrayNotHasKey('bedrooms', $facts);
    }

    public function test_monthly_ceiling_budget_is_annualized(): void
    {
        $facts = MessageFactExtractor::extract(['انا حدي 2200 شهري']);
        $this->assertEquals(26400.0, $facts['budget_max']);
    }

    // ─── Round-4 regressions ──────────────────────────────────────────────────

    public function test_it_extracts_amara_spelling_variant(): void
    {
        $facts = MessageFactExtractor::extract(['مطلوب عماره سكنيه نظيفه في جدة']);
        $this->assertContains($facts['type'] ?? null, ['عمارة', 'عمارة سكنية']);
        $this->assertSame('جدة', $facts['city'] ?? null);
    }

    public function test_it_extracts_shaqaq_plural_as_apartment(): void
    {
        $facts = MessageFactExtractor::extract(['أبي ثلاث شقق متجاورات غرفتين في الرياض']);
        $this->assertSame('شقة', $facts['type'] ?? null);
        $this->assertSame(2, $facts['bedrooms'] ?? null);
    }

    public function test_it_extracts_compound_million_and_thousands(): void
    {
        $facts = MessageFactExtractor::extract(['المشترى الى 5 مليون و 500 الف']);
        $this->assertEquals(5_500_000.0, $facts['budget_max']);
    }

    public function test_exclusion_list_is_not_a_district(): void
    {
        $facts = MessageFactExtractor::extract(['اي حي شمال ماعدا الوادي والندى والغدير']);
        $this->assertArrayNotHasKey('district', $facts);
    }

    public function test_king_salman_road_implies_riyadh(): void
    {
        $facts = MessageFactExtractor::extract(['مطلوب فله جنوب طريق الملك سلمان شارع 20']);
        $this->assertSame('الرياض', $facts['city'] ?? null);
        $this->assertSame('فيلا', $facts['type'] ?? null);
    }
}
