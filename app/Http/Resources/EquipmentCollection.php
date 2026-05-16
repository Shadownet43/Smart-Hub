<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\EquipmentStatus;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EquipmentCollection extends ResourceCollection
{
    /**
     * @var class-string<EquipmentResource>
     */
    public $collects = EquipmentResource::class;

    /**
     * Metadata tambahan pada respons koleksi (ringkasan status global).
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'summary' => [
                    'total_available' => Equipment::query()
                        ->where('status', EquipmentStatus::Available)
                        ->count(),
                    'total_borrowed' => Equipment::query()
                        ->where('status', EquipmentStatus::Borrowed)
                        ->count(),
                    'total_maintenance' => Equipment::query()
                        ->where('status', EquipmentStatus::Maintenance)
                        ->count(),
                ],
            ],
        ];
    }
}
