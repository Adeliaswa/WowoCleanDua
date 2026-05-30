<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\TrackingLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class GatewayController extends Controller
{
    #[OA\Get(
        path: "/api/v1/gateway/containers",
        summary: "Ambil semua data kontainer via Gateway (admin & user)",
        tags: ["Gateway - Containers"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "type", in: "query", required: false, schema: new OA\Schema(type: "string", example: "Chemical")),
            new OA\Parameter(name: "min_weight", in: "query", required: false, schema: new OA\Schema(type: "number", example: 500)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Daftar kontainer berhasil diambil"),
            new OA\Response(response: 401, description: "Unauthenticated"),
        ]
    )]
    public function index(Request $request)
    {
        $query = Container::with('trackingLogs');

        if ($request->filled('type')) {
            $query->whereRaw('LOWER(waste_type) = ?', [strtolower($request->type)]);
        }

        if ($request->filled('min_weight')) {
            $query->where('weight_kg', '>=', (float) $request->min_weight);
        }

        return response()->json($query->get(), 200);
    }

    public function show($id)
    {
        $container = Container::with('trackingLogs')->find($id);
        if (!$container) {
            return response()->json(['message' => 'Container not found'], 404);
        }
        return response()->json($container, 200);
    }

    public function logs($id)
    {
        $container = Container::with('trackingLogs')->find($id);
        if (!$container) {
            return response()->json(['message' => 'Container not found'], 404);
        }
        return response()->json([
            'container_id'  => $container->container_id,
            'tracking_logs' => $container->trackingLogs,
        ], 200);
    }

    #[OA\Post(
        path: "/api/v1/gateway/containers",
        summary: "Tambah kontainer baru via Gateway (admin only)",
        tags: ["Gateway - Containers"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["container_id", "waste_type", "weight_kg", "status"],
                properties: [
                    new OA\Property(property: "container_id", type: "string", example: "XY12345"),
                    new OA\Property(property: "waste_type",   type: "string", example: "Chemical"),
                    new OA\Property(property: "weight_kg",    type: "number", example: 500),
                    new OA\Property(property: "status",       type: "string", example: "Active"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Kontainer berhasil dibuat"),
            new OA\Response(response: 403, description: "Forbidden - bukan admin"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'container_id' => ['required', 'regex:/^[A-Za-z]{2}[0-9]{5}$/'],
            'waste_type'   => ['required', 'string'],
            'weight_kg'    => ['required', 'numeric', 'between:10,5000'],
            'status'       => ['required', 'in:Active,Archived'],
        ], [
            'container_id.required' => 'Container ID wajib diisi.',
            'container_id.regex'    => 'Format container_id harus 2 huruf + 5 angka.',
            'waste_type.required'   => 'Waste type wajib diisi.',
            'weight_kg.required'    => 'Weight wajib diisi.',
            'weight_kg.numeric'     => 'Weight harus berupa angka.',
            'weight_kg.between'     => 'Weight harus antara 10 sampai 5000 kg.',
            'status.required'       => 'Status wajib diisi.',
            'status.in'             => 'Status harus Active atau Archived.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $exists = Container::whereRaw('UPPER(container_id) = ?', [strtoupper($request->container_id)])->exists();
        if ($exists) {
            return response()->json(['message' => 'Validation failed', 'errors' => ['container_id' => ['Container ID harus unik.']]], 422);
        }

        if (strtolower($request->waste_type) === 'chemical' && $request->weight_kg > 1000) {
            return response()->json(['message' => 'Validation failed', 'errors' => ['weight_kg' => ['Untuk waste_type Chemical, weight_kg tidak boleh lebih dari 1000.']]], 422);
        }

        $container = Container::create([
            'container_id' => strtoupper($request->container_id),
            'waste_type'   => $request->waste_type,
            'weight_kg'    => (float) $request->weight_kg,
            'status'       => $request->status,
        ]);

        TrackingLog::create([
            'container_id' => $container->id,
            'location'     => 'Initial Entry',
            'timestamp'    => now(),
            'description'  => 'Container created via Gateway API',
        ]);

        return response()->json([
            'message' => 'Container created successfully',
            'data'    => $container->load('trackingLogs'),
        ], 201);
    }

    #[OA\Patch(
        path: "/api/v1/gateway/containers/{id}/archive",
        summary: "Arsipkan kontainer via Gateway (admin only)",
        tags: ["Gateway - Containers"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Berhasil diarsipkan"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function archive($id)
    {
        $container = Container::find($id);
        if (!$container) {
            return response()->json(['message' => 'Container not found'], 404);
        }

        $container->status = 'Archived';
        $container->save();

        TrackingLog::create([
            'container_id' => $container->id,
            'location'     => 'System Update',
            'timestamp'    => now(),
            'description'  => 'Container archived via Gateway',
        ]);

        return response()->json(['message' => 'Container archived successfully'], 200);
    }

    #[OA\Delete(
        path: "/api/v1/gateway/containers/{id}",
        summary: "Hapus kontainer via Gateway (admin only)",
        tags: ["Gateway - Containers"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Berhasil dihapus"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function destroy($id)
    {
        $container = Container::find($id);
        if (!$container) {
            return response()->json(['message' => 'Container not found'], 404);
        }

        $container->delete();

        return response()->json(['message' => 'Container deleted successfully'], 200);
    }
}