export type ServiceTimerPayload = {
    statusName: string | null;
    startedAt: string | null;
    pausedAt: string | null;
    totalElapsedSeconds: number;
    elapsedSeconds: number;
    isActive: boolean;
    isPaused: boolean;
};
