<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Enums\EquipmentStatus;
use App\Models\Booking;
use App\Models\Equipment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, array<int, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        return [
            'equipment_id' => ['required', 'exists:equipment,id'],
            'start_time' => ['required', 'date', 'after:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $data = $validator->getData();
            $equipmentId = (int) $data['equipment_id'];

            $equipment = Equipment::query()->find($equipmentId);

            if ($equipment === null) {
                return;
            }

            if ($equipment->status !== EquipmentStatus::Available) {
                $validator->errors()->add(
                    'equipment_id',
                    'Peralatan tidak tersedia untuk dipinjam (status bukan tersedia).',
                );

                return;
            }

            if ($equipment->stock < 1) {
                $validator->errors()->add(
                    'equipment_id',
                    'Stok peralatan habis, tidak dapat membuat peminjaman.',
                );

                return;
            }

            if (Booking::query()->overlapping(
                $equipmentId,
                $data['start_time'],
                $data['end_time'],
            )->exists()) {
                $validator->errors()->add(
                    'start_time',
                    'Jadwal bentrok dengan peminjaman lain pada peralatan ini. Pilih rentang waktu yang berbeda.',
                );
                $validator->errors()->add(
                    'end_time',
                    'Jadwal bentrok dengan peminjaman lain pada peralatan ini. Pilih rentang waktu yang berbeda.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'equipment_id.required' => 'Peralatan wajib dipilih.',
            'equipment_id.exists' => 'Peralatan yang dipilih tidak ditemukan.',
            'start_time.required' => 'Waktu mulai wajib diisi.',
            'start_time.date' => 'Format waktu mulai tidak valid.',
            'start_time.after' => 'Waktu mulai harus setelah sekarang.',
            'end_time.required' => 'Waktu selesai wajib diisi.',
            'end_time.date' => 'Format waktu selesai tidak valid.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
            'notes.string' => 'Catatan harus berupa teks.',
            'notes.max' => 'Catatan maksimal :max karakter.',
        ];
    }
}
