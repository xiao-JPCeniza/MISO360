<?php

namespace App\Http\Controllers;

use App\Models\IssuedUid;
use Illuminate\Database\QueryException;
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

    public function validateUid(string $uid)
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
