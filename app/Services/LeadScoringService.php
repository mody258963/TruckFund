<?php

namespace App\Services;

use App\Enums\LeadValue;

class LeadScoringService
{
    public function scoreFromInputs(array $data): int
    {
        $score = 100;
        if (! empty($data['price']) && $data['price'] > 500000) {
            $score += 40;
        }
        if (! empty($data['down_payment_pct']) && $data['down_payment_pct'] >= 30) {
            $score += 30;
        }
        if (! empty($data['email'])) {
            $score += 15;
        }

        return min($score, 250);
    }

    public function valueFromScore(int $score): LeadValue
    {
        if ($score >= 185) {
            return LeadValue::High;
        }
        if ($score >= 120) {
            return LeadValue::Mid;
        }

        return LeadValue::Low;
    }

    public function isPriority(int $score): bool
    {
        return $score >= config('truckfund.high_value_score_threshold', 185);
    }
}
