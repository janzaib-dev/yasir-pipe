<?php

namespace App\Http\Controllers\Hr\Traits;

trait SalaryStructureHelper
{
    /**
     * Validate and normalize deduction rules to ensure continuous ranges
     * 
     * Rules:
     * - Ranges must be continuous (no gaps)
     * - No overlapping ranges allowed
     * - Only one open-ended rule allowed (and it must be the last)
     * - max_minutes cannot be less than the calculated min_minutes
     * - Auto-calculates min_minutes based on previous rule's max_minutes + 1
     */
    protected function validateAndNormalizeDeductionRules(array $rules, string $fieldName): array
    {
        // Filter out empty rules
        $rules = collect($rules)->filter(function ($item) {
            return isset($item['amount']) && $item['amount'] !== '' && $item['amount'] !== null;
        })->values()->toArray();

        if (empty($rules)) {
            return ['rules' => [], 'error' => null];
        }

        $normalizedRules = [];
        $currentMin = 1; // First rule starts from 1 minute
        $hasOpenEnded = false;

        // Sort rules by max_minutes to ensure proper order (open-ended last)
        usort($rules, function ($a, $b) {
            $maxA = isset($a['max_minutes']) && $a['max_minutes'] !== '' && $a['max_minutes'] !== null 
                    ? (int)$a['max_minutes'] : PHP_INT_MAX;
            $maxB = isset($b['max_minutes']) && $b['max_minutes'] !== '' && $b['max_minutes'] !== null 
                    ? (int)$b['max_minutes'] : PHP_INT_MAX;
            return $maxA <=> $maxB;
        });

        foreach ($rules as $index => $rule) {
            $maxMinutes = isset($rule['max_minutes']) && $rule['max_minutes'] !== '' && $rule['max_minutes'] !== null 
                          ? (int)$rule['max_minutes'] : null;
            $amount = (float)$rule['amount'];
            $type = $rule['type'] ?? 'fixed';

            // Check if this is an open-ended rule
            $isOpenEnded = ($maxMinutes === null);

            // Validate: Only one open-ended rule allowed
            if ($isOpenEnded && $hasOpenEnded) {
                return [
                    'rules' => [],
                    'error' => 'Only one open-ended (unlimited) rule is allowed.'
                ];
            }

            // Validate: Open-ended rule must be the last rule
            if ($hasOpenEnded && !$isOpenEnded) {
                return [
                    'rules' => [],
                    'error' => 'Open-ended rule must be the last rule. No rules can be added after it.'
                ];
            }

            // Validate: max_minutes must be >= currentMin for non-open-ended rules
            if (!$isOpenEnded && $maxMinutes < $currentMin) {
                return [
                    'rules' => [],
                    'error' => "Rule " . ($index + 1) . ": Max minutes ({$maxMinutes}) cannot be less than the required minimum ({$currentMin})."
                ];
            }

            // Validate: Amount must be positive
            if ($amount < 0) {
                return [
                    'rules' => [],
                    'error' => "Rule " . ($index + 1) . ": Deduction amount cannot be negative."
                ];
            }

            // Build normalized rule
            $normalizedRules[] = [
                'min_minutes' => $currentMin,
                'max_minutes' => $maxMinutes,
                'type' => $type,
                'amount' => $amount,
            ];

            // Update currentMin for next rule (max + 1)
            if (!$isOpenEnded) {
                $currentMin = $maxMinutes + 1;
            }

            if ($isOpenEnded) {
                $hasOpenEnded = true;
            }
        }

        return ['rules' => $normalizedRules, 'error' => null];
    }
}
