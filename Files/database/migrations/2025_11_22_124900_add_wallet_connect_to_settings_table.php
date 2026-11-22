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
        // make sure it does not exist
        $settings = [
            'wallet_connect_enabled' => 0, // disabled by default,
            'wallet_connect_compulsory' => 0, // not compulsory by default
        ];
        foreach ($settings as $key => $value) {
            $existingSetting = Setting::where('key', $key)->first();
            if (!$existingSetting) {
                $settings = new Setting();
                $settings->key = $key;
                $settings->value = $value;
                $settings->save();
            }
        }
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         $settings = [
            'wallet_connect_enabled' => 0, // disabled by default,
            'wallet_connect_compulsory' => 0, // not compulsory by default
        ];
        foreach ($settings as $key => $value) {
            $existingSetting = Setting::where('key', $key)->first();
            if ($existingSetting) {
                $existingSetting->delete();
            }
        }
    }
};
