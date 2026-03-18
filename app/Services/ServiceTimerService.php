<?php

namespace App\Services;

use App\Models\TicketRequest;
use Carbon\Carbon;

class ServiceTimerService
{
    private const TIMEZONE = 'Asia/Manila';

    private const STATUS_INACTIVE = ['Pending', 'Unassigned'];

    private const STATUS_PAUSED = ['Paused', 'Put on Hold'];

    private const STATUS_ACTIVE = ['Ongoing'];

    /**
     * Compute service timer updates when status changes.
     * Returns array of column => value to merge into the update payload.
     *
     * @param  array{service_timer_started_at: \Carbon\Carbon|null, service_timer_paused_at: \Carbon\Carbon|null, service_timer_total_elapsed_seconds: int}  $current
     * @return array<string, mixed>
     */
    public function computeTimerUpdates(
        ?string $newStatusName,
        ?string $previousStatusName,
        array $current
    ): array {
        $now = Carbon::now(self::TIMEZONE);

        $isInactive = $this->isTimerInactiveStatus($newStatusName);
        $isPaused = $this->isPausedStatus($newStatusName);
        $isActive = $this->isActiveStatus($newStatusName);

        $wasInactive = $this->isTimerInactiveStatus($previousStatusName);
        $wasPaused = $this->isPausedStatus($previousStatusName);
        $wasActive = $this->isActiveStatus($previousStatusName);

        $startedAt = $current['service_timer_started_at'] ?? null;
        $pausedAt = $current['service_timer_paused_at'] ?? null;
        $totalElapsed = (int) ($current['service_timer_total_elapsed_seconds'] ?? 0);

        if ($isInactive) {
            return [
                'service_timer_started_at' => null,
                'service_timer_paused_at' => null,
                'service_timer_total_elapsed_seconds' => 0,
            ];
        }

        if ($isActive) {
            if ($wasInactive) {
                return [
                    'service_timer_started_at' => $now,
                    'service_timer_paused_at' => null,
                    'service_timer_total_elapsed_seconds' => 0,
                ];
            }
            if ($wasPaused) {
                return [
                    'service_timer_started_at' => $now,
                    'service_timer_paused_at' => null,
                    'service_timer_total_elapsed_seconds' => $totalElapsed,
                ];
            }

            return [];
        }

        if ($isPaused && $wasActive && $startedAt) {
            $startedAtParsed = Carbon::parse($startedAt, self::TIMEZONE);
            $elapsedThisRun = (int) $startedAtParsed->diffInSeconds($now, absolute: true);

            return [
                'service_timer_paused_at' => $now,
                'service_timer_total_elapsed_seconds' => $totalElapsed + $elapsedThisRun,
            ];
        }

        return [];
    }

    /**
     * Compute elapsed seconds for display (server-side snapshot).
     */
    public function computeElapsedSeconds(TicketRequest $ticket): int
    {
        $statusName = $ticket->status?->name;
        if ($this->isTimerInactiveStatus($statusName)) {
            return 0;
        }

        $total = (int) ($ticket->service_timer_total_elapsed_seconds ?? 0);
        if ($this->isPausedStatus($statusName)) {
            return $total;
        }

        if ($this->isActiveStatus($statusName) && $ticket->service_timer_started_at) {
            $started = Carbon::parse($ticket->service_timer_started_at, self::TIMEZONE);

            return $total + (int) Carbon::now(self::TIMEZONE)->diffInSeconds($started);
        }

        return $total;
    }

    public function isTimerInactiveStatus(?string $name): bool
    {
        if ($name === null || $name === '') {
            return true;
        }
        $n = strtolower(trim($name));

        return in_array($n, ['pending', 'unassigned'], true);
    }

    public function isPausedStatus(?string $name): bool
    {
        if ($name === null || $name === '') {
            return false;
        }
        $n = strtolower(trim($name));

        return in_array($n, ['paused', 'put on hold'], true);
    }

    public function isActiveStatus(?string $name): bool
    {
        if ($name === null || $name === '') {
            return false;
        }
        $n = strtolower(trim($name));

        return $n === 'ongoing';
    }
}
