<?php

function kategoriSigma(float $skor, float $mean, float $sd): string
{
    if ($sd == 0) return 'Cukup';
    $z = ($skor - $mean) / $sd;
    if ($z >= 1.5) return 'Sangat baik';
    if ($z >= 0.5) return 'Baik';
    if ($z >= -0.5) return 'Cukup';
    if ($z >= -1.5) return 'Kurang';
    return 'Sangat kurang';
}

function labelJenjang(?string $jenjang): string
{
    return match ($jenjang) {
        'SD1' => 'SD Kelas 1',
        'SD6' => 'SD Kelas 6',
        'SMP9' => 'SMP/MTs Kelas 9',
        default => '-',
    };
}
