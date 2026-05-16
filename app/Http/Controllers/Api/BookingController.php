<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CheckInRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            \assert($user instanceof User);

            $query = Booking::query()
                ->with(['equipment', 'user']);

            if (! $user->isAdmin()) {
                $query->forUser($user->id);
            }

            if ($request->filled('status')) {
                $status = (string) $request->query('status');
                if (BookingStatus::tryFrom($status) !== null) {
                    $query->byStatus($status);
                }
            }

            $paginator = $query->latest('start_time')->paginate(20)->withQueryString();

            return BookingResource::collection($paginator)
                ->additional([
                    'success' => true,
                    'message' => 'Daftar peminjaman berhasil diambil.',
                ])
                ->response();
        } catch (Throwable $e) {
            report($e);

            return $this->failureJson('Terjadi kesalahan saat mengambil daftar peminjaman.', 500);
        }
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            /** @var User $user */
            $user = $request->user();
            \assert($user instanceof User);

            $booking = Booking::query()->create([
                'user_id' => $user->id,
                'equipment_id' => (int) $data['equipment_id'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'notes' => $data['notes'] ?? null,
                'status' => BookingStatus::Pending,
            ]);

            $booking->load(['equipment', 'user']);

            return $this->successJson(
                (new BookingResource($booking))->resolve($request),
                'Peminjaman berhasil dibuat. Menunggu persetujuan administrator.',
                201,
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failureJson('Terjadi kesalahan saat membuat peminjaman.', 500);
        }
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        try {
            if (! $this->canAccessBooking($request, $booking)) {
                return $this->failureJson('Anda tidak memiliki akses ke peminjaman ini.', 403);
            }

            $booking->load(['equipment', 'user', 'approvedBy']);

            return $this->successJson(
                (new BookingResource($booking))->resolve($request),
                'Detail peminjaman berhasil diambil.',
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failureJson('Terjadi kesalahan saat mengambil detail peminjaman.', 500);
        }
    }

    public function update(UpdateBookingRequest $request, Booking $booking): JsonResponse
    {
        try {
            $data = $request->validated();
            $newStatus = BookingStatus::from($data['status']);

            $update = [
                'status' => $newStatus,
                'approved_by' => $request->user()?->id,
            ];

            $booking->update($update);

            $booking->load(['equipment', 'user', 'approvedBy']);

            return $this->successJson(
                (new BookingResource($booking->fresh()))->resolve($request),
                'Status peminjaman diperbarui.',
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failureJson('Terjadi kesalahan saat memperbarui peminjaman.', 500);
        }
    }

    public function checkIn(CheckInRequest $request, Booking $booking): JsonResponse
    {
        try {
            if (! $this->canAccessBooking($request, $booking)) {
                return $this->failureJson('Anda tidak memiliki akses ke peminjaman ini.', 403);
            }

            if (! $booking->canBeCheckedIn()) {
                return $this->failureJson(
                    'Check-in tidak dapat dilakukan pada status atau jadwal saat ini.',
                    422,
                );
            }

            $note = $request->validated()['notes'] ?? null;
            $merge = $booking->notes;
            if (is_string($note) && $note !== '') {
                $merge = trim(($merge !== null && $merge !== '' ? $merge."\n" : '').$note);
            }

            $booking->update([
                'checked_in_at' => now(),
                'notes' => $merge,
            ]);

            $booking->load(['equipment', 'user', 'approvedBy']);

            return $this->successJson(
                (new BookingResource($booking->fresh()))->resolve($request),
                'Check-in berhasil dicatat.',
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failureJson('Terjadi kesalahan saat check-in.', 500);
        }
    }

    public function checkOut(Request $request, Booking $booking): JsonResponse
    {
        try {
            if (! $this->canAccessBooking($request, $booking)) {
                return $this->failureJson('Anda tidak memiliki akses ke peminjaman ini.', 403);
            }

            if (! $booking->canBeCheckedOut()) {
                return $this->failureJson(
                    'Check-out tidak dapat dilakukan pada status saat ini.',
                    422,
                );
            }

            $booking->update([
                'checked_out_at' => now(),
                'status' => BookingStatus::Returned,
            ]);

            $booking->load(['equipment', 'user', 'approvedBy']);

            return $this->successJson(
                (new BookingResource($booking->fresh()))->resolve($request),
                'Check-out berhasil. Peralatan dikembalikan.',
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failureJson('Terjadi kesalahan saat check-out.', 500);
        }
    }

    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            \assert($user instanceof User);

            if ($booking->user_id !== $user->id && ! $user->isAdmin()) {
                return $this->failureJson('Anda tidak dapat membatalkan peminjaman ini.', 403);
            }

            if ($booking->status !== BookingStatus::Pending) {
                return $this->failureJson(
                    'Hanya peminjaman berstatus menunggu yang dapat dibatalkan.',
                    422,
                );
            }

            $booking->delete();

            return response()->json([
                'success' => true,
                'message' => 'Peminjaman berhasil dibatalkan.',
                'data' => null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->failureJson('Terjadi kesalahan saat membatalkan peminjaman.', 500);
        }
    }

    private function canAccessBooking(Request $request, Booking $booking): bool
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user instanceof User
            && ($user->isAdmin() || $booking->user_id === $user->id);
    }

    private function successJson(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function failureJson(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
        ], $status);
    }
}
