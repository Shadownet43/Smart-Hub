<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Enums\BookingStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(BookingStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status peminjaman wajib diisi.',
            'status.enum' => 'Status peminjaman tidak valid.',
        ];
    }
}
