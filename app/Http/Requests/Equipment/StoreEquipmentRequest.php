<?php

declare(strict_types=1);

namespace App\Http\Requests\Equipment;

use App\Enums\EquipmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100', Rule::unique('equipment', 'name')],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::enum(EquipmentStatus::class)],
            'stock' => ['nullable', 'integer', 'min:0', 'max:100'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama peralatan wajib diisi.',
            'name.string' => 'Nama peralatan harus berupa teks.',
            'name.max' => 'Nama peralatan maksimal :max karakter.',
            'name.unique' => 'Nama peralatan ini sudah digunakan.',
            'category.required' => 'Kategori wajib diisi.',
            'category.string' => 'Kategori harus berupa teks.',
            'category.max' => 'Kategori maksimal :max karakter.',
            'description.string' => 'Deskripsi harus berupa teks.',
            'description.max' => 'Deskripsi maksimal :max karakter.',
            'status.enum' => 'Status peralatan tidak valid.',
            'stock.integer' => 'Stok harus berupa angka bulat.',
            'stock.min' => 'Stok minimal :min.',
            'stock.max' => 'Stok maksimal :max.',
            'image.image' => 'Berkas harus berupa gambar.',
            'image.mimes' => 'Gambar harus berformat jpeg, jpg, png, atau webp.',
            'image.max' => 'Ukuran gambar maksimal :max kilobyte.',
        ];
    }
}
