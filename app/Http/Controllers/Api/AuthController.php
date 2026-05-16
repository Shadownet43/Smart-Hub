<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    private const string TOKEN_NAME = 'SmartHub-API';

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'role' => UserRole::Member,
        ]);

        $plainToken = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return $this->authJsonResponse(
            request: $request,
            user: $user->fresh(),
            token: $plainToken,
            message: 'Registrasi berhasil. Selamat datang di Smart-Hub.',
            success: true,
            status: 201,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau kata sandi tidak sesuai.',
                'data' => [
                    'user' => null,
                    'token' => null,
                    'token_type' => 'Bearer',
                ],
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        \assert($user instanceof User);

        $plainToken = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return $this->authJsonResponse(
            request: $request,
            user: $user,
            token: $plainToken,
            message: 'Login berhasil.',
            success: true,
            status: 200,
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token !== null) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Anda telah keluar dari sesi.',
            'data' => [
                'user' => null,
                'token' => null,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        \assert($user instanceof User);

        $user->load([
            'bookings' => static function ($query): void {
                $query->latest('start_time')->limit(10);
            },
            'bookings.equipment:id,name,category,status',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data pengguna berhasil diambil.',
            'data' => [
                'user' => $this->userPayload($user, includeBookings: true),
                'token' => null,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    /**
     * Respons autentikasi dengan struktur JSON konsisten.
     */
    private function authJsonResponse(
        Request $request,
        User $user,
        string $token,
        string $message,
        bool $success,
        int $status,
    ): JsonResponse {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => [
                'user' => UserResource::make($user)->resolve($request),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], $status);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user, bool $includeBookings = false): array
    {
        $payload = UserResource::make($user)->resolve(request());

        if ($includeBookings && $user->relationLoaded('bookings')) {
            $payload['bookings'] = $user->bookings->map(static function ($booking): array {
                return [
                    'id' => $booking->id,
                    'equipment_id' => $booking->equipment_id,
                    'status' => $booking->status->value,
                    'start_time' => $booking->start_time?->toIso8601String(),
                    'end_time' => $booking->end_time?->toIso8601String(),
                    'equipment' => $booking->relationLoaded('equipment') && $booking->equipment !== null
                        ? [
                            'id' => $booking->equipment->id,
                            'name' => $booking->equipment->name,
                            'category' => $booking->equipment->category,
                            'status' => $booking->equipment->status->value,
                        ]
                        : null,
                ];
            })->values()->all();
        }

        return $payload;
    }
}
