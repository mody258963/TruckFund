<?php

namespace Tests\Unit;

use App\Enums\LeadValue;
use App\Services\LeadScoringService;
use Tests\TestCase;

class LeadScoringServiceTest extends TestCase
{
    public function test_high_value_score_threshold(): void
    {
        $service = new LeadScoringService;
        $score = $service->scoreFromInputs([
            'price' => 600000,
            'down_payment_pct' => 35,
            'email' => 'a@b.com',
        ]);

        $this->assertGreaterThanOrEqual(185, $score);
        $this->assertEquals(LeadValue::High, $service->valueFromScore($score));
        $this->assertTrue($service->isPriority($score));
    }
}
