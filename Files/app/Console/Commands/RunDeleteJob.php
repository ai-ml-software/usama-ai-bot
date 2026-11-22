<?php

namespace App\Console\Commands;

use App\Models\CronJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RunDeleteJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:delete';

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
        $logPath = storage_path('logs');
        $logFiles = File::files($logPath);

        foreach ($logFiles as $logFile) {
            if (File::size($logFile) > 10 * 1024 * 1024) { // 10MB
                File::delete($logFile);
            }
        }


        // update the last run time
        $job = CronJob::where('name', 'delete-logs')->first();
        $update = CronJob::find($job->id);
        $update->last_run = time();
        $update->save();
    }
}
