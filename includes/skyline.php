<?php
declare(strict_types=1);

function skyline_criteria(): array
{
    return [
        'blood_pressure' => 'Blood Pressure',
        'blood_sugar' => 'Blood Sugar',
        'cholesterol' => 'Cholesterol',
        'bmi' => 'BMI',
        'pulse_rate' => 'Pulse Rate',
    ];
}

function record_dominates(array $candidate, array $other, array $preferences): bool
{
    $atLeastAsGood = true;
    $strictlyBetter = false;
    foreach ($preferences as $criterion => $direction) {
        $a = (float) $candidate[$criterion];
        $b = (float) $other[$criterion];
        if ($direction === 'min') {
            $atLeastAsGood = $atLeastAsGood && $a <= $b;
            $strictlyBetter = $strictlyBetter || $a < $b;
        } else {
            $atLeastAsGood = $atLeastAsGood && $a >= $b;
            $strictlyBetter = $strictlyBetter || $a > $b;
        }
        if (!$atLeastAsGood) {
            return false;
        }
    }
    return $strictlyBetter;
}

function compute_skyline(array $records, array $preferences): array
{
    if (count($preferences) < 2) {
        throw new InvalidArgumentException('Select at least two skyline criteria.');
    }
    $skyline = [];
    foreach ($records as $index => $record) {
        $dominated = false;
        foreach ($records as $otherIndex => $other) {
            if ($index !== $otherIndex && record_dominates($other, $record, $preferences)) {
                $dominated = true;
                break;
            }
        }
        if (!$dominated) {
            $skyline[] = $record;
        }
    }
    return [
        'analyzed' => count($records),
        'dominated' => count($records) - count($skyline),
        'skyline' => $skyline,
    ];
}
