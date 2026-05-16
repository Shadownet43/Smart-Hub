<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EquipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Equipment\StoreEquipmentRequest;
use App\Http\Requests\Equipment\UpdateEquipmentRequest;
use App\Http\Resources\EquipmentCollection;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class EquipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Equipment::query();

            if ($request->filled('search')) {
                $query->search((string) $request->query('search'));
            }

            if ($request->filled('category')) {
                $query->byCategory((string) $request->query('category'));
            }

            if ($request->filled('status')) {
                $status = (string) $request->query('status');
                if (EquipmentStatus::tryFrom($status) !== null) {
                    $query->byStatus($status);
                }
            }

            $paginator = $query->latest()->paginate(15)->withQueryString();

            return (new EquipmentCollection($paginator))
                ->additional([
                    'success' => true,
                    'message' => 'Daftar peralatan berhasil diambil.',
                ])
                ->response();
        } catch (Throwable $e) {
            report($e);

            return $this->failureResponse(
                'Terjadi kesalahan saat mengambil data peralatan.',
                500,
            );
        }
    }

    public function store(StoreEquipmentRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                unset($data['image']);
            }

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('equipment', 'public');
            }

            if (! array_key_exists('stock', $data) || $data['stock'] === null) {
                $data['stock'] = 1;
            }

            if (! array_key_exists('status', $data) || $data['status'] === null) {
                $data['status'] = EquipmentStatus::Available;
            }

            $equipment = Equipment::query()->create($data);

            return $this->successResponse(
                (new EquipmentResource($equipment->fresh()))->resolve($request),
                'Peralatan berhasil ditambahkan.',
                201,
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failureResponse(
                'Terjadi kesalahan saat menambahkan peralatan.',
                500,
            );
        }
    }

    public function show(Request $request, Equipment $equipment): JsonResponse
    {
        try {
            return $this->successResponse(
                (new EquipmentResource($equipment))->resolve($request),
                'Detail peralatan berhasil diambil.',
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failureResponse(
                'Terjadi kesalahan saat mengambil detail peralatan.',
                500,
            );
        }
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): JsonResponse
    {
        try {
            $data = $request->validated();

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                unset($data['image']);
            }

            if ($request->hasFile('image')) {
                $this->deleteStoredImageIfExists($equipment);

                $data['image'] = $request->file('image')->store('equipment', 'public');
            }

            if ($data !== []) {
                $equipment->update($data);
            }

            return $this->successResponse(
                (new EquipmentResource($equipment->fresh()))->resolve($request),
                'Peralatan berhasil diperbarui.',
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failureResponse(
                'Terjadi kesalahan saat memperbarui peralatan.',
                500,
            );
        }
    }

    public function destroy(Request $request, Equipment $equipment): JsonResponse
    {
        try {
            $this->deleteStoredImageIfExists($equipment);

            $equipment->delete();

            return $this->successResponse(
                null,
                'Peralatan berhasil dihapus.',
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failureResponse(
                'Terjadi kesalahan saat menghapus peralatan.',
                500,
            );
        }
    }

    private function successResponse(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function failureResponse(string $message, int $status = 400, mixed $data = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function deleteStoredImageIfExists(Equipment $equipment): void
    {
        if ($equipment->image === null || $equipment->image === '') {
            return;
        }

        if (Str::startsWith($equipment->image, ['http://', 'https://'])) {
            return;
        }

        Storage::disk('public')->delete($equipment->image);
    }
}
