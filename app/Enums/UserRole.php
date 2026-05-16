<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Contracts\DescribesBadge;
use ValueError;

enum UserRole: string implements DescribesBadge
{
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Member => 'Anggota',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'blue',
            self::Member => 'green',
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
                'Peran pengguna "%s" tidak valid. Gunakan: %s.',
                $value,
                implode(', ', array_map(fn (self $case): string => $case->value, self::cases())),
            ));
        }

        return $case;
    }
}
