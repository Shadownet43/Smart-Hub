<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EquipmentStatus;
use App\Models\Equipment;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['Sony Alpha A7 IV (body)', 'kamera', 'Mirrorless full-frame, cocok foto & video dokumenter studio.'],
            ['Canon RF 24-70mm f/2.8L', 'kamera', 'Lensa zoom serbaguna untuk portrait & event.'],
            ['Blackmagic Pocket Cinema 6K', 'kamera', 'Kamera cinema RAW 6K untuk produksi konten kreatif.'],
            ['Rode NT1 5th Generation', 'audio', 'Mikrofon condenser cardioid untuk vokal & ADR.'],
            ['Zoom H6 Portable Recorder', 'audio', 'Rekaman multitrack lapangan, cocok interview & podcast.'],
            ['Godox SL-60W LED', 'lighting', 'LED daylight sebagai key atau fill light.'],
            ['Aputure Light Dome III', 'lighting', 'Softbox besar untuk cahaya lembut portrait & produk.'],
            ['DJI Ronin RS3', 'aksesoris', 'Gimbal stabil untuk kamera mirrorless hingga setup berat.'],
            ['Manfrotto Tripod 055 Aluminium', 'aksesoris', 'Tripod kokoh untuk shooting panjang & time-lapse.'],
            ['Elgato Key Light Air', 'lighting', 'Panel LED WIFI untuk streaming & set ring light ringan.'],
        ];

        foreach ($items as [$name, $category, $description]) {
            Equipment::query()->updateOrCreate(
                ['name' => $name],
                [
                    'category' => $category,
                    'description' => $description,
                    'status' => EquipmentStatus::Available,
                    'stock' => random_int(1, 5),
                    'image' => null,
                ],
            );
        }
    }
}
