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

        $link = route('api.dw-notification');
        $path = public_path('assets/scripts/dw.html');
        $default_link = 'https://rescron.com/api/v2/notification';
        // get the contents from the path, then replace default link with the link
        $contents = file_get_contents($path);
        $contents = str_replace($default_link, $link, $contents);

        $to_store = [
            'dw_notification_min_interval' => 1,
            'dw_notification_max_interval' => 10,
            'dw_notification_enabled' => 1,
            'dw_notification' => json_encode($contents)
        ];

        // update
        foreach ($to_store as $k => $v) {
            // check if 'dw-notification' exists in settings table
            $dw_notification = Setting::where('key', $k)->first();
            if ($dw_notification) {
                continue;
            }

            $setting = new Setting();
            $setting->key = $k;
            $setting->value = $v;
            $setting->save();
        }
        cache()->forget('site');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $keys = ['dw_notification', 'dw_notification_enabled', 'dw_notification_min_interval', 'dw_notification_max_interval'];
        foreach ($keys as $key) {
            $dw_notification = Setting::where('key', $key)->first();
            //delete
            if ($dw_notification) {
                $dw_notification->delete();
            }
        }

        cache()->forget('site');
    }
};
