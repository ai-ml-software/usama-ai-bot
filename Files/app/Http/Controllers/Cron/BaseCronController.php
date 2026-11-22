<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\Models\CronJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class BaseCronController extends Controller
{
    public function botCronOne()
    {

        Artisan::call('schedule:run');

        return true;
    }


    // delete logs
    public function deleteLogs()
    {
        Artisan::call('schedule:run');
        return true;
    }

    // start payment
    
}
