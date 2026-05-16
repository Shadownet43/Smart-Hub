<?php

declare(strict_types=1);

namespace App\Enums\Contracts;

/**
 * Kontrak untuk enum bertipe backed string yang dapat ditampilkan sebagai label & warna badge.
 */
interface DescribesBadge
{
    public function label(): string;

    /**
     * Warna badge UI: green | yellow | red | blue.
     */
    public function color(): string;
}
