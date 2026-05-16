<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => UserResource::make($this->whenLoaded('user')),
            'equipment' => EquipmentResource::make($this->whenLoaded('equipment')),
            'start_time' => $this->formatLongDateTime($this->start_time),
            'end_time' => $this->formatLongDateTime($this->end_time),
            'duration_hours' => $this->duration_in_hours,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'notes' => $this->notes,
            'checked_in_at' => $this->formatLongDateTime($this->checked_in_at),
            'checked_out_at' => $this->formatLongDateTime($this->checked_out_at),
            'is_overdue' => $this->is_overdue,
            'approved_by' => $this->whenLoaded('approvedBy', function (): ?UserResource {
                return $this->approvedBy !== null
                    ? new UserResource($this->approvedBy)
                    : null;
            }),
            'created_at' => $this->formatLongDateTime($this->created_at),
        ];
    }

    private function formatLongDateTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $carbon = $value instanceof Carbon ? $value->copy() : Carbon::parse($value);

        return $carbon->locale('id')->isoFormat('dddd, D MMMM YYYY HH:mm');
    }
}
