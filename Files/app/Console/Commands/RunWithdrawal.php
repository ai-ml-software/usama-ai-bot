<?php

namespace App\Console\Commands;

use App\Models\WithdrawalViewer;
use Illuminate\Console\Command;

class RunWithdrawal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'withdraw:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Withdrawal notification to telegram';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // return;
        //get the last withdrawal watch

        $functions  = [
            'fetchUsdtTransactions',
            'fetchEthTransactions',
            'fetchUsdtTransactionsBep20',
            'fetchUsdtTransactions',
            'fetchEthTransactions',
            'fetchUsdtTransactionsBep20',
            'fetchUsdtTransactions',
            'fetchEthTransactions',
            'fetchUsdtTransactionsBep20',
        ];

        shuffle($functions);


        $withdrawal = WithdrawalViewer::orderBy('id', 'DESC')->first();
        if (!$withdrawal) {
            $functions[0]();
            return; //first initialization
        }

        //check if the next time is less than current time
        if ($withdrawal->next_time < time()) {
            $functions[0]();
            return;
        }
    }
}
