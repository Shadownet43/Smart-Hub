<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\EquipmentStatus;
use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property-read string $image_url
 * @property-read bool $is_available
 *
 * @method static Builder|Equipment available()
 * @method static Builder|Equipment byCategory(string $category)
 * @method static Builder|Equipment search(string $keyword)
 * @method static Builder|Equipment byStatus(string $status)
 */
class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'category',
        'description',
        'status',
        'stock',
        'image',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EquipmentStatus::class,
            'stock' => 'integer',
        ];
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Peminjaman yang masih berlangsung: menunggu atau disetujui.
     *
     * @return HasMany<Booking, $this>
     */
    public function activeBookings(): HasMany
    {
        return $this->hasMany(Booking::class)
            ->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Approved->value,
            ]);
    }

    /**
     * Hanya peralatan tersedia dengan stok positif.
     *
     * @param  Builder<Equipment>  $query
     * @return Builder<Equipment>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('status', EquipmentStatus::Available)
            ->where('stock', '>', 0);
    }

    /**
     * @param  Builder<Equipment>  $query
     * @return Builder<Equipment>
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * @param  Builder<Equipment>  $query
     * @return Builder<Equipment>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Pencarian berdasarkan nama atau deskripsi.
     *
     * @param  Builder<Equipment>  $query
     * @return Builder<Equipment>
     */
    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return $query;
        }

        return $query->where(static function (Builder $inner) use ($keyword): void {
            $pattern = '%'.addcslashes($keyword, '%_\\').'%';

            $inner
                ->where('name', 'like', $pattern)
                ->orWhere('description', 'like', $pattern);
        });
    }

    /**
     * URL lengkap gambar untuk atribut `src` pada elemen img atau respons API JSON.
     */
    protected function getImageUrlAttribute(): string
    {
        if (blank($this->image)) {
            return self::defaultImageUrl();
        }

        if (Str::startsWith($this->image, ['http://', 'https://'])) {
            return $this->image;
        }

        return Storage::disk('public')->url($this->image);
    }

    /**
     * True jika status tersedia dan masih ada stok.
     */
    protected function getIsAvailableAttribute(): bool
    {
        return $this->status === EquipmentStatus::Available && $this->stock > 0;
    }

    /**
     * Fallback aman tanpa bergantung pada asset statis di repo.
     */
    private static function defaultImageUrl(): string
    {
        return 'https://placehold.co/480x320/e2e8f0/475569/png?text=Smart-Hub+Equipment';
    }
}
