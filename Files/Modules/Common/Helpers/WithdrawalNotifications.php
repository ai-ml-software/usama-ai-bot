<?php

use App\Models\WithdrawalViewer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

function fetchUsdtTransactions()
{
    $url = "https://apilist.tronscanapi.com/api/transfer/trc20?address=TWS1onJnNTg8tJHomceqxBxTsUB1DHh7PV&trc20Id=TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t&start=0&limit=10&direction=1&reverse=true&db_version=1&start_timestamp=&end_timestamp=";
    $response = Http::get($url);
    if (!$response->successful()) {
        return false;
    }

    $data = $response->json('data');

    // Log::info('USDT Transactions Data', ['data' => $data]);

    $key = 0;
    $withdrawal = [];
    $continue = true;
    $divider = 1000000;
    while ($key <= 9 && $continue) {
        if ($data[$key]['amount']  > (80 * $divider) && $data[$key]['amount'] < (600 * $divider)) {
            $withdrawal = $data[$key];
            $continue = false;
        }
        $key++;
    }


    if (!isset($withdrawal['amount'])) {
        return false; // or return null, false, or handle as needed
    }
    $amount = floatval($withdrawal['amount'] / $divider);

    $link = 'https://tronscan.org/#/transaction/' . $withdrawal['hash'];
    saveWithdrawalViewer($amount, $withdrawal['hash'], $withdrawal['to'], time(), $link, 'USDT');
    // $message = "*USDT WITHDRAWAL* \n\n♻️Time: " . date('d-m-y H:i:s') .  " UTC \n\n♻️Amount: " . $amount .   "USDT \n\n♻️Address: " . $withdrawal['to'] .  "\n\n♻️Hash: " . $withdrawal['hash'] .  "\n\n♻️Exporer: " . $link .  "\n\nView All Payout via  " . route('payouts') .  "\n\nView all trading history via  " . route('history') ;
    $timestamp = date('d-m-Y H:i:s');          // e.g. 15-07-25 16:05:12
    $link      = $link ?? '';                  // Explorer URL

    $message  = "*💸 USDT WITHDRAWAL ALERT 💸*\n\n";
    $message .= "⏰ *Time:* `{$timestamp} UTC`\n\n";
    $message .= "💵 *Amount:* *{$amount} USDT TRC20*\n\n";
    $message .= "🏦 *Address:* `{$withdrawal['to']}`\n\n";
    $message .= "🔗 *Hash:* {$link}";
    if (function_exists('sendMessageTelegram')) {
        sendMessageTelegram($message);
    }
}

function fetchEthTransactions()
{
    $target_address = strtolower('0x077D360f11D220E4d5D831430c81C26c9be7C4A4');
    $url = "https://api.etherscan.io/api?module=account&action=txlist&address=" . $target_address . "&startblock=0&endblock=99999999&page=1&offset=10&sort=desc&apikey=EPHB7I9CRDXTV9R5RC1UTHE94ZAYQH9768";
    $response = Http::get($url);
    if (!$response->successful()) {
        return false;
    }

    $data = $response->json('result');
    // Log::info('ETH Transactions Data', ['data' => $data]);
    $divider = 1000000000000000000;

    // $amount = floatval($data[0]['value'] / $divider);
    // dd($amount, $data[0]['value']);


    $key = 0;
    $withdrawal = [];
    $continue = true;
    while ($key <= 9 && $continue) {
        if ($data[$key]['value']  > floatval(0.3 * $divider) && $data[$key]['value'] < floatval(0.7 * $divider) && $data[$key]['from'] == $target_address) {
            $withdrawal = $data[$key];
            $continue = false;
        }
        $key++;
    }

    // dd($withdrawal['from'], $target_address);

    if (!isset($withdrawal['amount'])) {
        return false; // or return null, false, or handle as needed
    }
    $amount = floatval($withdrawal['value'] / $divider);

    $link = 'https://etherscan.io/tx/' . $withdrawal['hash'];
    saveWithdrawalViewer($amount, $withdrawal['hash'], $withdrawal['to'], time(), $link, 'ETH');
    // $message = "*ETH WITHDRAWAL* \n\n♻️Time: " . date('d-m-y H:i:s') .  " UTC \n\n♻️Amount: " . $amount .   "ETH \n\n♻️Address: " . $withdrawal['to'] .  "\n\n♻️Hash: " . $withdrawal['hash'] .  "\n\n♻️Exporer: " . $link .  "\n\nView All Payout via  " . route('payouts') .  "\n\nView all trading history via  " . route('history');
    $timestamp = date('d-m-Y H:i:s');          // e.g. 15-07-25 16:05:12
    $link      = $link ?? '';                  // Explorer URL

    $message  = "*💸 ETH WITHDRAWAL ALERT 💸*\n\n";
    $message .= "⏰ *Time:* {$timestamp} UTC\n\n";
    $message .= "💵 *Amount:* *{$amount} ETH*\n\n";
    $message .= "🏦 *Address:* `{$withdrawal['to']}`\n\n";
    // $message .= "🔗 *Hash:* [`{$withdrawal['hash']}`]({$link})";
    $message .= "🔗 *Hash:* {$link}";


    if (function_exists('sendMessageTelegram')) {
        sendMessageTelegram($message);
    }
}


function fetchUsdtTransactionsBep20()
{
    $url = "https://api.etherscan.io/v2/api?chainid=56&module=account&action=tokentx&address=0xA96Be652A08D9905F15B7FbE2255708709BeCD09&contractaddress=0x55d398326f99059ff775485246999027b3197955&page=1&offset=10&startblock=0&endblock=99999999&sort=desc&apikey=HWTTMUFVKH6UF7HD74JP4WB5HPYKGZHW3P";

    $response = Http::get($url);

    if (!$response->successful()) {
        return false;
    }

    $data = $response->json('result');
    // Log::info('BEP20 USDT Transactions Data', ['data' => $data]);

    $key = 0;
    $withdrawal = [];
    $continue = true;
    $divider = 1e18; // BEP20 USDT (18 decimals)

    while ($key <= 9 && $continue && isset($data[$key])) {
        $rawAmount = floatval($data[$key]['value'] ?? 0);
        if ($rawAmount > (80 * $divider) && $rawAmount < (600 * $divider)) {
            $withdrawal = $data[$key];
            $continue = false;
        }
        $key++;
    }

    if (!isset($withdrawal['value'])) {
        return false;
    }

    $amount = floatval($withdrawal['value'] / $divider);
    $hash = $withdrawal['hash'] ?? '';
    $to = $withdrawal['to'] ?? '';
    $link = "https://bscscan.com/tx/{$hash}";

    // Save the withdrawal in your system
    saveWithdrawalViewer($amount, $hash, $to, time(), $link, 'USDT');

    $timestamp = date('d-m-Y H:i:s');
    $message  = "*💸 USDT WITHDRAWAL ALERT 💸*\n\n";
    $message .= "⏰ *Time:* `{$timestamp} UTC`\n\n";
    $message .= "💵 *Amount:* *{$amount} USDT BEP20*\n\n";
    $message .= "🏦 *Address:* `{$to}`\n\n";
    $message .= "🔗 *Hash:* {$link}";

    if (function_exists('sendMessageTelegram')) {
        sendMessageTelegram($message);
    }
}



// function fetchBtcTransactions()
// {
//     $target_address = strtolower('bc1qkrpagyh06crzjc8e5xnlk29j8qrrw9erquktyq');
//     $url = "https://api.blockchair.com/bitcoin/dashboards/address/$target_address";
//     $response = Http::get($url);
//     if (!$response->successful()) {
//         return false;
//     }

//     dd($response->json());

//     $data = $response->json('result');
//     $divider = 1000000000000000000;

//     // $amount = floatval($data[0]['value'] / $divider);
//     // dd($amount, $data[0]['value']);


//     $key = 0;
//     $withdrawal = [];
//     $continue = true;
//     while ($key <= 9 && $continue) {
//         if ($data[$key]['value']  > floatval(0.0251 * $divider) && $data[$key]['value'] < floatval(0.5 * $divider) && $data[$key]['from'] == $target_address) {
//             $withdrawal = $data[$key];
//             $continue = false;
//         }
//         $key++;
//     }

//     // dd($withdrawal['from'], $target_address);


//     $amount = floatval($withdrawal['value'] / $divider);

//     $link = 'https://etherscan.io/tx/' . $withdrawal['hash'];
//     saveWithdrawalViewer($amount, $withdrawal['hash'], $withdrawal['to'], time(), $link, 'ETH');
//     $message = "*ETH WITHDRAWAL* \n\n♻️Time: " . date('d-m-y H:i:s') .  " UTC \n\n♻️Amount: " . $amount .   "ETH \n\n♻️Address: " . $withdrawal['to'] .  "\n\n♻️Hash: " . $withdrawal['hash'] .  "\n\n♻️Exporer: " . $link;
//     if (function_exists('sendMessageTelegram')) {
//         sendMessageTelegram($message);
//     }

// }



function saveWithdrawalViewer($amount, $hash, $wallet, $timestamp, $explorer, $code)
{
    $store = new WithdrawalViewer();
    $store->amount = $amount;
    $store->hash = $hash;
    $store->wallet = $wallet;
    $store->timestamp = $timestamp;
    $store->explorer = $explorer;
    $store->code = $code;
    $store->next_time = now()->addMinutes(rand(30, 90))->timestamp;
    $store->save();
    return true;
}
