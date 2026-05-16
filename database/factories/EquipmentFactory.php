<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EquipmentStatus;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $preset = fake()->randomElement($this->presets());

        return [
            'name' => $preset['name'],
            'category' => $preset['category'],
            'description' => $preset['description'],
            'status' => fake()->randomElement(array_map(
                static fn (EquipmentStatus $s): string => $s->value,
                EquipmentStatus::cases()
            )),
            'stock' => fake()->numberBetween(1, 5),
            'image' => null,
        ];
    }

    /**
     * @return list<array{name: string, category: string, description: string}>
     */
    private function presets(): array
    {
        return [
            [
                'name' => 'Sony Alpha A7 IV (body)',
                'category' => 'kamera',
                'description' => 'Mirrorless full-frame 33MP, cocok untuk foto & video studio.',
            ],
            [
                'name' => 'Canon EOS R6 Mark II',
                'category' => 'kamera',
                'description' => 'Full-frame hybrid, autofokus cepat untuk acara & dokumenter.',
            ],
            [
                'name' => 'Tripod Manfrotto 055 XPRO3',
                'category' => 'aksesoris',
                'description' => 'Tinggi hingga 170cm, kaki aluminium, cocok untuk kamera berat.',
            ],
            [
                'name' => 'DJI Ronin RS3 Pro',
                'category' => 'aksesoris',
                'description' => 'Gimbal 3-axis untuk setup kamera cinema hingga 4.5kg.',
            ],
            [
                'name' => 'Rode NT1 5th Generation',
                'category' => 'audio',
                'description' => 'Condenser cardioid ultra-diam untuk rekaman vokal/instrumen.',
            ],
            [
                'name' => 'Shure SM7B',
                'category' => 'audio',
                'description' => 'Dynamic mic standar podcast & vokal, tahan noise ruangan.',
            ],
            [
                'name' => 'Sennheiser MKE 600',
                'category' => 'audio',
                'description' => 'Shotgun untuk kamera, cocok interview lapangan & studio.',
            ],
            [
                'name' => 'Godox SL-60W LED',
                'category' => 'lighting',
                'description' => 'Lampu LED daylight 5600K, mount Bowens, untuk key light.',
            ],
            [
                'name' => 'Nanlite Forza 300 II',
                'category' => 'lighting',
                'description' => 'COB LED daylight, output tinggi untuk set portrait & produk.',
            ],
            [
                'name' => 'Aputure MC Pro RGBWW',
                'category' => 'lighting',
                'description' => 'Panel LED portabel RGB, fill accent & efek warna.',
            ],
            [
                'name' => 'Profoto B10X Plus',
                'category' => 'lighting',
                'description' => 'Flash battery portable 500Ws TTL, mudah dipindah antar set.',
            ],
            [
                'name' => 'Zoom F6 Multitrack Field Recorder',
                'category' => 'audio',
                'description' => 'Recorder 6 channel 32-bit float, cocok produksi lapangan.',
            ],
        ];
    }
}
