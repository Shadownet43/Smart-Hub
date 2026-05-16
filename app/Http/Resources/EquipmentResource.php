<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Equipment
 */
class EquipmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'stock' => $this->stock,
            'is_available' => $this->is_available,
            'image_url' => $this->image_url,
            'created_at' => $this->created_at?->locale('id')->translatedFormat('j F Y H:i'),
            'updated_at' => $this->updated_at?->locale('id')->translatedFormat('j F Y H:i'),
            'bookings_count' => $this->whenCounted('bookings'),
            'active_bookings' => $this->whenLoaded('activeBookings', function (): array {
                return $this->activeBookings->map(static function ($booking): array {
                    return [
                        'id' => $booking->id,
                        'user_id' => $booking->user_id,
                        'status' => $booking->status->value,
                        'start_time' => $booking->start_time?->toIso8601String(),
                        'end_time' => $booking->end_time?->toIso8601String(),
                    ];
                })->values()->all();
            }),
        ];
    }
}
