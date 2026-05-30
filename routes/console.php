<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:expire-trials')->hourly();
Schedule::command('voice-samples:purge')->everyMinute();
Schedule::command('pool-share:distribute')->dailyAt('23:59');
Schedule::command('admin-reports:snapshot')->dailyAt('23:59');
Schedule::command('pool-share:refund-weekly')->weeklyOn(0, '23:59');
