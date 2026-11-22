<?php

namespace App\Console\Commands;

use App\Models\CronJob;
use Illuminate\Console\Command;

class RunCronJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = json_decode(site('trading_days'));
        $today = strtolower(date("l"));
        //end running bots
        endBot();
        if (in_array($today, $days)) {
            //update trade timestamp
            updateTimestamp();
            // updateTimestamp();

            //run bot
            runBot();
        }

        

        // update the last run time
        $job = CronJob::where('name', 'bot-cron-one')->first();
        $update = CronJob::find($job->id);
        $update->last_run = time();
        $update->save();

        return;
    }
}
