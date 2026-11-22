<?php

namespace App\Console\Commands;

use App\Services\Web3Service;
use Illuminate\Console\Command;

class Bip39Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bip39-command';

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
        $this->info('Creating BIP-39 word list JSON files...');
        $storage_folder = resource_path('json/bip-39');
        $web3Service = new Web3Service();
        $created = $web3Service::createBip39WordListJsonFiles($storage_folder);
        if ($created['status'] === 'success') {
            $this->info($created['message']);
        } else {
            $this->error($created['message']);
            return Command::FAILURE;
        }

        return Command::SUCCESS;

    }

}
