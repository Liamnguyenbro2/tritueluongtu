<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:expire-trials')->hourly();
Schedule::command('voice-samples:purge')->everyMinute();
Schedule::command('pool-share:distribute')->dailyAt('23:59');
