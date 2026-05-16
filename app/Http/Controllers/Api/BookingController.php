<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([]);
    }

    public function show(Booking $booking): JsonResponse
    {
        return response()->json([]);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        return response()->json([]);
    }

    public function checkIn(Request $request, Booking $booking): JsonResponse
    {
        return response()->json([]);
    }

    public function checkOut(Request $request, Booking $booking): JsonResponse
    {
        return response()->json([]);
    }

    public function destroy(Booking $booking): JsonResponse
    {
        return response()->json([]);
    }
}
