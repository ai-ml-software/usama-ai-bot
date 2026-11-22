<?php

//give profit for running bot

use App\Models\BotActivation;
use App\Models\BotHistory;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

function runBot()
{
    // $response = Http::get('https://api.binance.com/api/v3/ticker/24hr');
    // dd($response->body());

    if (!consolidateSecurity()) {
        return false;
    }
    $baseUrls = [
        'https://api.binance.us',
        'https://api.binance.com',

    ];

    $tickerData = null;

    foreach ($baseUrls as $baseUrl) {
        try {
            $response = Http::get($baseUrl . '/api/v3/ticker/24hr');
            $response->throw(); // This will throw an exception if the response status is not successful

            $tickerData = $response->json();
            break; // Stop the loop if successful
        } catch (\Exception $e) {
            // Log or handle the error if needed
            continue; // Try the next URL
        }
    }



    if ($tickerData === null) {
        // Handle the case where both URLs failed
        // For example, throw an exception or log an error
        Log::error('API Connection error');
        return 'Api Connection Error';
    }

    // Filter and sort pairs based on quote volume
    $usdtPairs = collect($tickerData)
        ->filter(function ($pair) {
            return str_ends_with($pair['symbol'], 'USDT') && !str_starts_with($pair['symbol'], 'USD');
        })
        ->sortByDesc('priceChangePercent')
        ->take(10);

    // dd($usdtPairs);

    //loser pairs
    // Filter and sort pairs based on quote volume
    $loserUsdtPairs = collect($tickerData)
        ->filter(function ($pair) {
            return str_ends_with($pair['symbol'], 'USDT') && str_contains($pair['priceChangePercent'], '-') && !str_starts_with($pair['symbol'], 'USD');
        })
        ->sortByDesc('priceChangePercent')
        ->take(10);

    $bot_activations = BotActivation::where('status', 'active')->where('next_trade', '<', time())->take(50)->get();
    foreach ($bot_activations as $act) {

        $timestamps = json_decode($act->gen_timestamps);
        $percentages = json_decode($act->daily_sequence);

        //dd(json_decode($act->daily_sequence));
        // if ($percentage == 0) {
        //     return false;
        // }


        if (count($timestamps) > 0 && count($percentages) > 0) {

            $percentage = reset($percentages);
            $timestamp = reset($timestamps);


            // loser mode
            if ($act->user->loser_mode == 1 && $percentage > 0) {
                $percentage = $percentage * -1;  //reverts the value to negative
            }

            if ($percentage > 0) {
                $randomPair = $usdtPairs->shuffle()->first();
            } else {
                $randomPair = $loserUsdtPairs->shuffle()->first();
            }



            //calculate entry and exit price
            if ($percentage == 0) {
                $entry_price = $randomPair['lastPrice'];
                $exit_price = $randomPair['lastPrice'];
            } elseif ($percentage < 0) {
                //loss 

                $exit_price = $randomPair['lastPrice'];
                $entry_price = $randomPair['lastPrice'] - ($randomPair['lastPrice'] * $percentage / 100);
            } elseif ($percentage > 0) {
                // profit
                $exit_price = $randomPair['lastPrice'];
                $entry_price = $randomPair['lastPrice'] - ($randomPair['lastPrice'] * $percentage / 100);
            }

            //confirm timestamp 
            $current_date = date('dm', time());
            $daily_date = date('dm', $act->daily_timestamp);

            //calculate daily return 
            $bot = $act->bot();

            // if (site('bot_compounding') == 1) {
            //     $return  = $act->balance * $percentage / 100;
            // } else {
            //     $return  = $act->capital * $percentage / 100;
            // }

            $return  = $act->capital * $percentage / 100;


            if (
                $current_date == $daily_date &&
                time() > $timestamp
            ) {
                $decodedSequence = json_decode($act->daily_sequence, true);
                if (is_array($decodedSequence) && count($decodedSequence) > 0) {
                    array_shift($decodedSequence); // Remove the first element
                    $daily_sequence = json_encode($decodedSequence);
                } else {
                    $daily_sequence = [];
                    $daily_sequence = json_encode($daily_sequence);
                }

                $decodedTimestamps = json_decode($act->gen_timestamps, true);
                if (is_array($decodedTimestamps) && count($decodedTimestamps) > 0) {
                    array_shift($decodedTimestamps); // Remove the first element
                    $next_trade = $decodedTimestamps[0] ?? null;
                    $gen_timestamps = json_encode($decodedTimestamps);
                } else {
                    $gen_timestamps = [];
                    $next_trade = null;
                    $gen_timestamps = json_encode($gen_timestamps);
                }

                if ($exit_price >  0) {
                    //add to the user bot activation instance balance
                    $bal = BotActivation::find($act->id);
                    $bal->balance = $act->balance + $return;
                    $bal->daily_profit = $act->daily_profit + $return; // update daily profit
                    $bal->profit = $act->profit + $return;
                    $bal->daily_sequence  = $daily_sequence;
                    $bal->gen_timestamps = $gen_timestamps;
                    $bal->next_trade = $next_trade;
                    $bal->save();

                    //create new bot history
                    $history = new BotHistory();
                    $history->user_id = $act->user_id;
                    $history->bot_id = $act->bot_id;
                    $history->bot_activation_id = $act->id;
                    $history->pair = $randomPair['symbol'];
                    $history->entry_price = $entry_price;
                    $history->exit_price = $exit_price;
                    $history->profit = $return;
                    $history->profit_percent = $percentage;
                    $history->capital = $act->capital;
                    $history->timestamp = $timestamp;
                    $history->save();

                    // Your message content

                    $pair = $randomPair['symbol'];
                    $profit = formatAmount($return);
                    $profit_percentage = $percentage . '%';
                    $message = "*New Trade Notification* \n🚀Exit Time: " . date('d-m-y H:i:s', $timestamp) .  " UTC \n🚀Trading Pair: " . $pair .  "\n🚀Portfolio: " . formatAmount($act->capital) .  "\n🚀Entry Price: " . $entry_price .  "\n🚀Exit Price: " . $exit_price .  "\n🚀Profit: " . $profit .  "\n🚀Gain: " . $profit_percentage;
                    if (function_exists('sendMessageTelegram')) {
                        sendMessageTelegram($message);
                    }
                }

                // return true;
            }
        }

        // return false;
    }


    return true;
}

//update daily timestamp
function updateTimestamp()
{
    // Generate timestamp for the new day
    $today_start = Carbon::today()->startOfDay()->timestamp;
    $today_end = Carbon::today()->endOfDay()->timestamp;


    // // Fetch only 20 records matching the criteria
    // $bot_activations = BotActivation::where('daily_timestamp', '<', $today_start)
    //     ->orWhere('daily_timestamp', '>', $today_end)
    //     ->where('status', 'active')
    //     ->take(20)
    //     ->get();


    $bot_activations = BotActivation::where('status', 'active')
        ->where(function ($query) use ($today_start, $today_end) {
            $query->where('daily_timestamp', '<', $today_start)
                ->orWhere('daily_timestamp', '>', $today_end);
        })
        ->take(20)
        ->get();

    // dd($bot_activations);

    foreach ($bot_activations as $act) {
        $bot = $act->bot;
        $trade_data = tradeData($bot);


        // credit the user the amount that was realized for that day
        if ($act->daily_profit > 0) {
            $user = User::find($act->user_id);
            $user->balance = $user->balance + $act->daily_profit;
            $user->save();
            recordNewTransaction($act->daily_profit, $user->id, 'credit', 'AI Bot profit');
        }

        //update timestamp
        $update = BotActivation::find($act->id);
        $update->daily_timestamp = time();
        if ($act->daily_profit > 0) {
            $update->daily_profit = 0; //reset the daily profit to zero
        }
        $update->gen_timestamps = json_encode($trade_data['timestamps']);
        $update->daily_sequence = json_encode($trade_data['sequence']);
        $update->next_trade = $trade_data['timestamps'][0] ?? null;
        $update->save();
    }


    return true;
}







//change the status of all completed bots
function endBot()
{

    BotActivation::where('status', 'active')
        ->where('expires_in', '<', time())
        ->chunk(100, function ($bot_activations) {
            foreach ($bot_activations as $act) {
                $update = BotActivation::find($act->id);
                $update->status = 'expired';
                $update->balance = 0;
                $update->save();

                //credit the user
                $user = $act->user;
                $credit = User::find($user->id);
                $credit->balance = $user->balance + $act->capital;
                $credit->save();

                //record transaction
                recordNewTransaction($act->capital, $user->id, 'credit', 'AI Bot Capital');
            }
        });

    return true;
}


// trade data
function tradeData($bot)
{
    //daily sequence
    $intervals = rand(site('bot_min_trade'), site('bot_max_trade'));

    $daily_min = $bot->daily_min;
    $daily_max = $bot->daily_max;
    $percentages = [$daily_min, $daily_max];
    $percentage_count = count($percentages);
    while ($percentage_count <= 15) {
        $first_number = $percentages[array_rand($percentages)];
        $second_number = $percentages[array_rand($percentages)];
        $third_number = $percentages[array_rand($percentages)];
        $average = array_sum([$first_number, $second_number, $third_number]) / 3;
        $average = round($average, 2);
        array_push($percentages, $average);
        $percentage_count++;
    }


    $total_percentage = $percentages[array_rand($percentages)];

    $query = [
        'intervals' => $intervals,
        'percentage' => $total_percentage,
    ];

    $url = endpoint('trade-data');
    $response = Http::withHeader('X-DOMAIN', domain())->get($url, $query);

    if ($response->successful()) {
        return  $response->json();
    }
    Log::error($response->body());
    return false;
}
