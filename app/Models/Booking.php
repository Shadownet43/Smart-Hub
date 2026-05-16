<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read float $duration_in_hours
 * @property-read bool $is_overdue
 *
 * @method static Builder|Booking forUser(int $userId)
 * @method static Builder|Booking byStatus(BookingStatus|string $status)
 * @method static Builder|Booking upcoming()
 * @method static Builder|Booking overlapping(int $equipmentId, CarbonInterface|string $start, CarbonInterface|string $end)
 */
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'equipment_id',
        'start_time',
        'end_time',
        'status',
        'notes',
        'checked_in_at',
        'checked_out_at',
        'approved_by',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Equipment, $this>
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Admin yang menyetujui peminjaman.
     *
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeByStatus(Builder $query, BookingStatus|string $status): Builder
    {
        $value = $status instanceof BookingStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    /**
     * Peminjaman mendatang yang sudah disetujui.
     *
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('start_time', '>', now())
            ->where('status', BookingStatus::Approved);
    }

    /**
     * Jadwal yang bertabrakan dengan interval [$start, $end] pada peralatan yang sama.
     * Hanya mempertimbangkan status yang masih relevan (pending / approved).
     *
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeOverlapping(
        Builder $query,
        int $equipmentId,
        CarbonInterface|string $start,
        CarbonInterface|string $end,
    ): Builder {
        $rangeStart = Carbon::parse($start);
        $rangeEnd = Carbon::parse($end);

        return $query
            ->where('equipment_id', $equipmentId)
            ->where('start_time', '<', $rangeEnd)
            ->where('end_time', '>', $rangeStart)
            ->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Approved->value,
            ]);
    }

    /**
     * Durasi terjadwal dalam jam (pecahan).
     */
    protected function getDurationInHoursAttribute(): float
    {
        $start = $this->start_time;
        $end = $this->end_time;

        if ($start === null || $end === null) {
            return 0.0;
        }

        $minutes = abs($start->diffInMinutes($end));

        return round($minutes / 60, 2);
    }

    /**
     * Sudah melewati batas akhir peminjaman tetapi status belum dikembalikan.
     */
    protected function getIsOverdueAttribute(): bool
    {
        return $this->status !== BookingStatus::Returned
            && $this->end_time !== null
            && now()->isAfter($this->end_time);
    }

    /**
     * Bisa check-in: disetujui, belum check-in, masih dalam jendela mulai–selesai.
     */
    public function canBeCheckedIn(): bool
    {
        if ($this->status !== BookingStatus::Approved) {
            return false;
        }

        if ($this->checked_in_at !== null) {
            return false;
        }

        $now = now();

        return $this->start_time !== null
            && $this->end_time !== null
            && $now->greaterThanOrEqualTo($this->start_time)
            && $now->lessThanOrEqualTo($this->end_time);
    }

    /**
     * Bisa check-out: sudah check-in, belum check-out, peminjaman masih aktif (disetujui).
     */
    public function canBeCheckedOut(): bool
    {
        return $this->status === BookingStatus::Approved
            && $this->checked_in_at !== null
            && $this->checked_out_at === null;
    }
}
