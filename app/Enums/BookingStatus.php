<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Contracts\DescribesBadge;
use ValueError;

enum BookingStatus: string implements DescribesBadge
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Returned => 'Dikembalikan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Returned => 'blue',
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
                'Status peminjaman "%s" tidak valid. Gunakan: %s.',
                $value,
                implode(', ', array_map(fn (self $case): string => $case->value, self::cases())),
            ));
        }

        return $case;
    }
}
