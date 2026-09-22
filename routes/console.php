<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('opportunities:collect')
    ->daily()
    ->when(fn (): bool => (bool) config('opportunity.schedule_collection'))
    ->withoutOverlapping(120);
