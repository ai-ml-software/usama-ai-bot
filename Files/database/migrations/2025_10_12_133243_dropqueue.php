<?php

use App\Models\CronJob;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // check if queue-work exists in the cron_job table and drop it
        $cron_job = CronJob::where('name', 'queue-work')->first();
        if ($cron_job) {
            $cron_job->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
