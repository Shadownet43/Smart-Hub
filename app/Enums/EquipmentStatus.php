<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Contracts\DescribesBadge;
use ValueError;

enum EquipmentStatus: string implements DescribesBadge
{
    case Available = 'available';
    case Borrowed = 'borrowed';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Borrowed => 'Dipinjam',
            self::Maintenance => 'Perawatan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'green',
            self::Borrowed => 'yellow',
            self::Maintenance => 'red',
        };
    }

    /**
     * Konversi aman dari string ke enum; melempar ValueError jika nilai tidak dikenal.
     */
    public static function dari(string $value): self
    {
        $case = self::tryFrom($value);

        if ($case === null) {
            throw new ValueError(sprintf(
                'Status peralatan "%s" tidak valid. Gunakan: %s.',
                $value,
                implode(', ', array_map(fn (self $case): string => $case->value, self::cases())),
            ));
        }

        return $case;
    }
}
