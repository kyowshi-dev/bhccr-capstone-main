<?php

namespace Tests\Unit;

use App\DTOs\MaternalQueueDTO;
use Tests\TestCase;

class MaternalQueueDTOTest extends TestCase
{
    private function makeDto(array $overrides = []): MaternalQueueDTO
    {
        return new MaternalQueueDTO(
            patient_id: $overrides['patient_id'] ?? 1,
            patient_name: $overrides['patient_name'] ?? 'Doe, Jane',
            patient_code: $overrides['patient_code'] ?? 'PT001',
            program_type: $overrides['program_type'] ?? 'prenatal',
            risk_level: $overrides['risk_level'] ?? 'normal',
            due_date: $overrides['due_date'] ?? '2026-08-20',
            primary_subtitle: $overrides['primary_subtitle'] ?? 'Test subtitle',
            is_checked_in_today: $overrides['is_checked_in_today'] ?? false,
            has_active_consultation: $overrides['has_active_consultation'] ?? false,
            active_consultation_id: $overrides['active_consultation_id'] ?? null,
            consultation_id: $overrides['consultation_id'] ?? null,
            program_record_id: $overrides['program_record_id'] ?? null,
            context_badges: $overrides['context_badges'] ?? [],
            is_grouped: $overrides['is_grouped'] ?? false,
            is_overdue: $overrides['is_overdue'] ?? false,
        );
    }

    public function test_for_grouped_card_returns_null_for_empty_collection(): void
    {
        $result = MaternalQueueDTO::forGroupedCard(collect());

        $this->assertNull($result);
    }

    public function test_for_grouped_card_returns_single_item_when_one_dto(): void
    {
        $dto = $this->makeDto(['program_type' => 'prenatal']);
        $result = MaternalQueueDTO::forGroupedCard(collect([$dto]));

        $this->assertNotNull($result);
        $this->assertTrue($result->is_grouped);
        $this->assertEquals('prenatal', $result->program_type);
        $this->assertEmpty($result->context_badges);
    }

    public function test_for_grouped_card_picks_earliest_due_date_as_primary(): void
    {
        $later = $this->makeDto([
            'patient_id' => 1,
            'program_type' => 'prenatal',
            'due_date' => '2026-08-25',
            'primary_subtitle' => 'Prenatal subtitle',
        ]);
        $earlier = $this->makeDto([
            'patient_id' => 1,
            'program_type' => 'fp',
            'due_date' => '2026-08-10',
            'primary_subtitle' => 'FP subtitle',
        ]);

        $result = MaternalQueueDTO::forGroupedCard(collect([$later, $earlier]));

        $this->assertNotNull($result);
        $this->assertEquals('2026-08-10', $result->due_date);
        $this->assertEquals('FP subtitle', $result->primary_subtitle);
    }

    public function test_for_grouped_card_merges_context_badges(): void
    {
        $prenatal = $this->makeDto([
            'patient_id' => 1,
            'program_type' => 'prenatal',
            'due_date' => '2026-08-20',
        ]);
        $fp = $this->makeDto([
            'patient_id' => 1,
            'program_type' => 'fp',
            'due_date' => '2026-08-15',
        ]);

        $result = MaternalQueueDTO::forGroupedCard(collect([$prenatal, $fp]));

        $this->assertNotNull($result);
        $this->assertCount(2, $result->context_badges);
        $types = collect($result->context_badges)->pluck('program_type')->all();
        $this->assertContains('prenatal', $types);
        $this->assertContains('fp', $types);
    }

    public function test_for_grouped_card_escalates_risk_to_high(): void
    {
        $normal = $this->makeDto([
            'patient_id' => 1,
            'program_type' => 'prenatal',
            'risk_level' => 'normal',
            'due_date' => '2026-08-20',
        ]);
        $highRisk = $this->makeDto([
            'patient_id' => 1,
            'program_type' => 'postnatal',
            'risk_level' => 'high',
            'due_date' => '2026-08-15',
        ]);

        $result = MaternalQueueDTO::forGroupedCard(collect([$normal, $highRisk]));

        $this->assertNotNull($result);
        $this->assertEquals('high', $result->risk_level);
    }

    public function test_for_grouped_card_detects_active_consultation(): void
    {
        $noConsult = $this->makeDto([
            'patient_id' => 1,
            'program_type' => 'prenatal',
            'due_date' => '2026-08-20',
            'has_active_consultation' => false,
        ]);
        $withConsult = $this->makeDto([
            'patient_id' => 1,
            'program_type' => 'fp',
            'due_date' => '2026-08-15',
            'has_active_consultation' => true,
            'active_consultation_id' => 42,
        ]);

        $result = MaternalQueueDTO::forGroupedCard(collect([$noConsult, $withConsult]));

        $this->assertNotNull($result);
        $this->assertTrue($result->has_active_consultation);
        $this->assertEquals(42, $result->active_consultation_id);
    }

    public function test_badge_label_returns_correct_strings(): void
    {
        $this->assertEquals('Prenatal Due', $this->makeDto(['program_type' => 'prenatal'])->badgeLabel());
        $this->assertEquals('Prenatal Overdue', $this->makeDto(['program_type' => 'prenatal', 'is_overdue' => true])->badgeLabel());
        $this->assertEquals('Postnatal Due', $this->makeDto(['program_type' => 'postnatal'])->badgeLabel());
        $this->assertEquals('FP Overdue', $this->makeDto(['program_type' => 'fp', 'is_overdue' => true])->badgeLabel());
        $this->assertEquals('Walk-in', $this->makeDto(['program_type' => 'triage'])->badgeLabel());
        $this->assertEquals('Walk-in', $this->makeDto(['program_type' => 'unknown'])->badgeLabel());
    }
}
