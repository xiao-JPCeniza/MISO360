<?php

namespace App\Http\Controllers;

use App\Models\IssuedUid;
use App\Models\QrBatch;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QrCodeController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $quantity = (int) $validated['quantity'];
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return DB::transaction(function () use ($quantity) {
                    $currentMax = IssuedUid::lockForUpdate()->max('sequence') ?? 0;
                    $startNumber = $currentMax + 1;
                    $endNumber = $startNumber + $quantity - 1;

                    $ids = [];
                    $payload = [];
                    $timestamp = now();

                    for ($sequence = $startNumber; $sequence <= $endNumber; $sequence++) {
                        $uid = $this->formatUid($sequence);
                        $ids[] = $uid;
                        $payload[] = [
                            'uid' => $uid,
                            'sequence' => $sequence,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    }

                    IssuedUid::insert($payload);

                    QrBatch::create([
                        'start_sequence' => $startNumber,
                        'end_sequence' => $endNumber,
                    ]);

                    $nextStart = (IssuedUid::max('sequence') ?? 0) + 1;
                    $totalGenerated = IssuedUid::count();

                    return response()->json([
                        'ids' => $ids,
                        'nextStart' => $nextStart,
                        'totalGenerated' => $totalGenerated,
                    ], 201);
                });
            } catch (QueryException $exception) {
                $attempt++;
                if ($attempt >= $maxRetries) {
                    return response()->json([
                        'message' => 'Unable to reserve this UID range. Please try again.',
                    ], 409);
                }
                // Brief pause before retry to reduce contention
                usleep(10000); // 10ms
            }
        }
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $batches = QrBatch::query()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'start_sequence', 'end_sequence', 'created_at']);

        return response()->json([
            'batches' => $batches->map(fn (QrBatch $batch) => [
                'id' => $batch->id,
                'start' => $batch->start_sequence,
                'end' => $batch->end_sequence,
                'createdAt' => $batch->created_at->toIso8601String(),
                'count' => $batch->end_sequence - $batch->start_sequence + 1,
            ]),
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function show(QrBatch $batch)
    {
        return response()->json([
            'ids' => $batch->ids,
        ]);
    }

    public function validateUid(string $uid): JsonResponse
    {
        $uid = strtoupper(trim($uid));

        return response()->json([
            'valid' => IssuedUid::where('uid', $uid)->exists(),
        ]);
    }

    private function formatUid(int $sequence): string
    {
        return sprintf('MIS-UID-%05d', $sequence);
    }
}
