<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ContainerController extends Controller
{
    private function getInitialData()
    {
        return [
            [
                'id' => 1,
                'container_id' => 'WH00001',
                'waste_type' => 'Chemical',
                'weight_kg' => 850,
                'status' => 'Active',
                'tracking_logs' => [
                    [
                        'location' => 'Warehouse A',
                        'timestamp' => '2026-04-15 08:00:00',
                        'description' => 'Container received at warehouse',
                    ],
                    [
                        'location' => 'Checkpoint 1',
                        'timestamp' => '2026-04-15 12:30:00',
                        'description' => 'Weight verification completed',
                    ],
                ],
            ],
            [
                'id' => 2,
                'container_id' => 'GD00002',
                'waste_type' => 'Plastic',
                'weight_kg' => 1200,
                'status' => 'Active',
                'tracking_logs' => [
                    [
                        'location' => 'Warehouse B',
                        'timestamp' => '2026-04-15 09:10:00',
                        'description' => 'Container packed',
                    ],
                ],
            ],
            [
                'id' => 3,
                'container_id' => 'AB00003',
                'waste_type' => 'Metal',
                'weight_kg' => 500,
                'status' => 'Archived',
                'tracking_logs' => [
                    [
                        'location' => 'Warehouse C',
                        'timestamp' => '2026-04-14 10:00:00',
                        'description' => 'Archived after final disposal',
                    ],
                ],
            ],
        ];
    }

    private function getData()
    {
        $path = 'containers.json';

        if (!Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, json_encode($this->getInitialData(), JSON_PRETTY_PRINT));
        }

        $json = Storage::disk('local')->get($path);
        return json_decode($json, true);
    }

    private function saveData($data)
    {
        Storage::disk('local')->put('containers.json', json_encode($data, JSON_PRETTY_PRINT));
    }

    public function index()
    {
        return response()->json($this->getData(), 200);
    }

    public function show($id)
    {
        $containers = $this->getData();

        $container = collect($containers)->firstWhere('id', (int) $id);

        if (!$container) {
            return response()->json([
                'message' => 'Container not found'
            ], 404);
        }

        return response()->json($container, 200);
    }

    public function logs($id)
    {
        $containers = $this->getData();

        $container = collect($containers)->firstWhere('id', (int) $id);

        if (!$container) {
            return response()->json([
                'message' => 'Container not found'
            ], 404);
        }

        return response()->json([
            'container_id' => $container['container_id'],
            'tracking_logs' => $container['tracking_logs']
        ], 200);
    }

    public function search(Request $request)
    {
        $containers = collect($this->getData());

        if ($request->filled('type')) {
            $type = strtolower($request->type);
            $containers = $containers->filter(function ($item) use ($type) {
                return strtolower($item['waste_type']) === $type;
            });
        }

        if ($request->filled('min_weight')) {
            $minWeight = (float) $request->min_weight;
            $containers = $containers->filter(function ($item) use ($minWeight) {
                return $item['weight_kg'] >= $minWeight;
            });
        }

        return response()->json(array_values($containers->toArray()), 200);
    }

    public function store(Request $request)
    {
        $containers = $this->getData();

        $validator = Validator::make($request->all(), [
            'container_id' => ['required', 'regex:/^[A-Za-z]{2}[0-9]{5}$/'],
            'waste_type' => ['required', 'string'],
            'weight_kg' => ['required', 'numeric', 'between:10,5000'],
            'status' => ['required', 'in:Active,Archived'],
        ], [
            'container_id.required' => 'Container ID wajib diisi.',
            'container_id.regex' => 'Format container_id harus 2 huruf + 5 angka.',
            'waste_type.required' => 'Waste type wajib diisi.',
            'weight_kg.required' => 'Weight wajib diisi.',
            'weight_kg.numeric' => 'Weight harus berupa angka.',
            'weight_kg.between' => 'Weight harus antara 10 sampai 5000 kg.',
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status harus Active atau Archived.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($containers as $item) {
            if (strtoupper($item['container_id']) === strtoupper($request->container_id)) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => [
                        'container_id' => ['Container ID harus unik.']
                    ]
                ], 422);
            }
        }

        if (strtolower($request->waste_type) === 'chemical' && $request->weight_kg > 1000) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'weight_kg' => ['Untuk waste_type Chemical, weight_kg tidak boleh lebih dari 1000.']
                ]
            ], 422);
        }

        $newId = count($containers) > 0 ? max(array_column($containers, 'id')) + 1 : 1;

        $newContainer = [
            'id' => $newId,
            'container_id' => strtoupper($request->container_id),
            'waste_type' => $request->waste_type,
            'weight_kg' => (float) $request->weight_kg,
            'status' => $request->status,
            'tracking_logs' => [
                [
                    'location' => 'Initial Entry',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'description' => 'Container created via API',
                ]
            ],
        ];

        $containers[] = $newContainer;
        $this->saveData($containers);

        return response()->json([
            'message' => 'Container created successfully',
            'data' => $newContainer
        ], 201);
    }

    public function archive($id)
    {
        $containers = $this->getData();
        $found = false;

        foreach ($containers as &$container) {
            if ($container['id'] == $id) {
                $container['status'] = 'Archived';
                $container['tracking_logs'][] = [
                    'location' => 'System Update',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'description' => 'Container archived',
                ];
                $found = true;
                break;
            }
        }

        if (!$found) {
            return response()->json([
                'message' => 'Container not found'
            ], 404);
        }

        $this->saveData($containers);

        return response()->json([
            'message' => 'Container archived successfully'
        ], 200);
    }

    public function destroy($id)
    {
        $containers = $this->getData();
        $before = count($containers);

        $containers = array_values(array_filter($containers, function ($item) use ($id) {
            return $item['id'] != $id;
        }));

        if (count($containers) === $before) {
            return response()->json([
                'message' => 'Container not found'
            ], 404);
        }

        $this->saveData($containers);

        return response()->json([
            'message' => 'Container deleted successfully'
        ], 200);
    }
}