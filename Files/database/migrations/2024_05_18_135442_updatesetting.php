<?php

use App\Models\Setting;
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
        $setting = Setting::where('key', 'payment_processor')->first();
        if (!$setting) {
            $add = new Setting();
            $add->key = 'payment_processor';
            $add->value = 'nowpayment';
            $add->save();
        }

        

         // update env
         $envs = [
            "BINANCE_API_KEY" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
            "BINANCE_SECRET_KEY" => "xxxxxxxxxxxxxxxxxx",
            "COINPAYMENT_MARCHANT_ID" => "xxxxxxxxxxxxxxxxx",
            "BINANCE_AMOUNT" => 100000,
        ];
        $env_file_path = base_path('.env');
        $original_env = file_get_contents($env_file_path);
        $new_env = $original_env;
        foreach($envs as $key => $value) {
            if (!env($key)) {
                $new_env .=  "\n" . $key . '="'.$value.'"';
            }
        }
       
        file_put_contents($env_file_path, $new_env);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
