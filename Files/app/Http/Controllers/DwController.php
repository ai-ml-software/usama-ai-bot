<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\DepositCoin;
use Illuminate\Http\Request;

class DwController extends Controller
{
    public function index()
    {
        $response_data = cache()->get('dw-data');
        if (!$response_data) {

            $actions = ['deposited', 'withdrew', 'deposited', 'withdrew', 'deposited', 'withdrew', 'deposited', 'withdrew', 'deposited', 'withdrew', 'deposited', 'withdrew'];
            $action = $actions[rand(0, count($actions))];
            $min_amount = site('min_deposit');
            $max_amount = site('max_deposit');
            if ($action == 'withdrew') {
                $min_amount = site('min_withdrawal');
                $max_amount = site('max_withdrawal');
            }

            $amount = rand($min_amount, $max_amount);
            $amount = $amount + rand(0, 99) / 100; 
            $amount = formatAmount($amount);
            $currency = site('currency');


            // order by random
            $coin = DepositCoin::where('status', 1)->inRandomOrder()->first();
            if (!$coin) {
                abort(404);
            }

            $action_currency = $coin->code;
            // Generate first and last name with fake
            $first_name = fake()->lastName;
            $last_name = fake()->firstName; //pick only first letter then add .
            $name = $first_name . ' ' . substr($last_name, 0, 1) . '.';
            $country  = fake()->country;
            $locale = //get from country
            $icon = 'https://nowpayments.io' . $coin->logo_url;
            $text = $name . ' from ' . $country . ' just ' . $action . ' ' . $amount . ' via ' . $action_currency;

            $response_data = [
                "amount" => $amount,
                "currency" => $currency,
                "action_currency" => $action_currency,
                "name" => $name,
                "action" => $action,
                "country" => $country,
                "icon" => $icon,
                "text" => $text,
                "timestamp" => now()->timestamp,
                "public_ref" => fake()->uuid(),
            ];
            // calculate sections

            $minutes_min = site('dw_notification_min_interval') ?? 1;
            $minutes_max = site('dw_notification_max_interval') ?? 10;
            $minutes = rand($minutes_min, $minutes_max);
            cache()->put('dw-data', $response_data, now()->addMinutes($minutes));
        }

        // overwrite text and append time ago
        $time_ago = $this->timeAgo($response_data['timestamp']);
        $response_data['text'] = $response_data['text'] . ' - ' . $time_ago;
        return response()->json($response_data);



    }

    // calculate time ago
    private function timeAgo($timestamp)
    {
        $current_time = time();
        $time_difference = $current_time - $timestamp;

        if ($time_difference < 1) return 'Just now';

        $seconds = $time_difference;
        $minutes = round($seconds / 60);
        $hours = round($seconds / 3600);
        $days = round($seconds / 86400);
        $weeks = round($seconds / 604800);
        $months = round($seconds / 2629440);
        $years = round($seconds / 31553280);

        if ($seconds < 60) {
            return "$seconds seconds ago";
        } elseif ($minutes < 60) {
            return "$minutes minutes ago";
        } elseif ($hours < 24) {
            return "$hours hours ago";
        } elseif ($days < 7) {
            return "$days days ago";
        } elseif ($weeks < 4) {
            return "$weeks weeks ago";
        } elseif ($months < 12) {
            return "$months months ago";
        } else {
            return "$years years ago";
        }
    }

}
