<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:expire-trials')->hourly();
Schedule::command('pool-share:distribute')->dailyAt('23:59');
