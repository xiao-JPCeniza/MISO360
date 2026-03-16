<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('queue:prune-batches --hours=168')->daily();
Schedule::command('model:prune')->daily();

Schedule::call(function (): void {
    DB::table('two_factor_challenges')
        ->where('expires_at', '<', now()->subDays(7))
        ->delete();
})->daily();
