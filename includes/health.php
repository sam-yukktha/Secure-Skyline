<?php
declare(strict_types=1);

function health_analysis(array $record): array
{
    $bmiValue = bmi((float) $record['weight'], (float) $record['height']);
    return [
        ['label' => 'Pulse rate', 'value' => $record['pulse_rate'] . ' bpm', 'status' => ((float) $record['pulse_rate'] >= 60 && (float) $record['pulse_rate'] <= 100) ? 'Within common adult resting range' : 'Outside common adult resting range', 'level' => ((float) $record['pulse_rate'] >= 60 && (float) $record['pulse_rate'] <= 100) ? 'ok' : 'warn'],
        ['label' => 'Blood pressure (systolic)', 'value' => $record['blood_pressure'] . ' mmHg', 'status' => (float) $record['blood_pressure'] < 120 ? 'Below 120' : ((float) $record['blood_pressure'] < 140 ? 'Elevated range' : 'High range'), 'level' => (float) $record['blood_pressure'] < 120 ? 'ok' : 'warn'],
        ['label' => 'Blood sugar', 'value' => $record['blood_sugar'] . ' mg/dL', 'status' => (float) $record['blood_sugar'] < 140 ? 'Below demonstration threshold' : 'At or above demonstration threshold', 'level' => (float) $record['blood_sugar'] < 140 ? 'ok' : 'warn'],
        ['label' => 'Cholesterol', 'value' => $record['cholesterol'] . ' mg/dL', 'status' => (float) $record['cholesterol'] < 200 ? 'Below demonstration threshold' : 'At or above demonstration threshold', 'level' => (float) $record['cholesterol'] < 200 ? 'ok' : 'warn'],
        ['label' => 'BMI', 'value' => number_format($bmiValue, 1), 'status' => $bmiValue < 18.5 ? 'Below common adult range' : ($bmiValue < 25 ? 'Within common adult range' : 'Above common adult range'), 'level' => ($bmiValue >= 18.5 && $bmiValue < 25) ? 'ok' : 'warn'],
    ];
}
