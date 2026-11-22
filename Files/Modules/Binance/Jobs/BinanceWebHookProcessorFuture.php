<?php

namespace Modules\Binance\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class BinanceWebHookProcessorFuture implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $data = $this->data;
        // set leverage
        $set_leverage = $this->setLeverage($data);
        if (!$set_leverage) {
            return;
        }

        // margin type
        $set_margin = $this->setMargin($data);

        // open position;
        $order = $this->openPosition($data);

        if (!$order) {
            return;
        }
        $quantity = $order['origQty'];

        // Place a take profit order
        $take_profit_order = $this->setTakeProfit($data, $quantity);

        // send to telegram
        $this->sendToTelegram($data);
        return;
    }

    // set the leverage
    private function setLeverage($data)
    {
        $apiEndpoint = 'https://fapi.binance.com/fapi/v1/leverage';

        $apiKey = env('BINANCE_API_KEY');
        $apiSecret = env('BINANCE_SECRET_KEY');
        $leverage = $data['leverage'];
        $symbol = $data['symbol'];


        // Prepare the payload for the trade
        $payload = [
            'symbol' => $symbol,
            'leverage' => $leverage,
            'timestamp' => time() * 1000
        ];

        // Generate the signature for the trade
        $queryString = http_build_query($payload);
        $signature = hash_hmac('sha256', $queryString, $apiSecret);

        // Add the signature to the payload
        $payload['signature'] = $signature;


        try {
            $client = new Client();
            $response = $client->request('POST', $apiEndpoint, [
                'headers' => [
                    'X-MBX-APIKEY' => $apiKey,
                ],
                'form_params' => $payload
            ]);
            return $tradeResponse = json_decode($response->getBody(), true);
        } catch (RequestException $e) {


            Log::error($e);

            return false;
        }
    }

    // set the leverage
    private function setMargin($data)
    {
        $apiEndpoint = 'https://fapi.binance.com/fapi/v1/marginType';

        $apiKey = env('BINANCE_API_KEY');
        $apiSecret = env('BINANCE_SECRET_KEY');
        $symbol = $data['symbol'];
        $margin = $data['margin'];


        // Prepare the payload for the trade
        $payload = [
            'symbol' => $symbol,
            'marginType' => strtoupper($margin),
            'timestamp' => time() * 1000
        ];

        // Generate the signature for the trade
        $queryString = http_build_query($payload);
        $signature = hash_hmac('sha256', $queryString, $apiSecret);

        // Add the signature to the payload
        $payload['signature'] = $signature;


        try {
            $client = new Client();
            $response = $client->request('POST', $apiEndpoint, [
                'headers' => [
                    'X-MBX-APIKEY' => $apiKey,
                ],
                'form_params' => $payload
            ]);
            return $tradeResponse = json_decode($response->getBody(), true);
        } catch (RequestException $e) {


            Log::error($e);

            return false;
        }
    }


    // open position
    private function openPosition($data)
    {
        $apiEndpoint = 'https://fapi.binance.com/fapi/v1/order';

        $apiKey = env('BINANCE_API_KEY');
        $apiSecret = env('BINANCE_SECRET_KEY');

        $leverage = $data['leverage'];
        $amount = floatval(env('BINANCE_AMOUNT')) * $leverage;


        $entryPrice = $data['entryPrice'];
        $quantity = $amount / $entryPrice;
        $symbol = $data['symbol'];

        // Format the quantity, stop price, and limit price to the correct precision
        $precision = $data['qPrecision'];
        $formattedQuantity = number_format($quantity, $precision, '.', '');

        // Prepare the payload for the trade
        $payload = [
            'symbol' => $symbol,
            'side' => 'BUY',
            'type' => 'MARKET',
            'quantity' => $formattedQuantity,
            'timestamp' => time() * 1000
        ];

        // Generate the signature for the trade
        $queryString = http_build_query($payload);
        $signature = hash_hmac('sha256', $queryString, $apiSecret);

        // Add the signature to the payload
        $payload['signature'] = $signature;


        // Execute the trade

        try {
            $client = new Client();
            $response = $client->request('POST', $apiEndpoint, [
                'headers' => [
                    'X-MBX-APIKEY' => $apiKey,
                ],
                'form_params' => $payload
            ]);

            $tradeResponse = json_decode($response->getBody(), true);
            $orderId = $tradeResponse['orderId'];

            return $tradeResponse;



            // Handle the response of the opposite trade
            // ...
        } catch (RequestException $e) {
            // Handle the exception for the opposite trade request
            // ...

            $error = [
                'error' => $e,
                'data' => $data,
                'amount' => $amount,
            ];
            Log::error($e);
            Log::error(json_encode($payload));
            return false;
        }
    }

    // open take profit and stop loss
    private function setTakeProfit($data, $quantity)
    {
        $apiEndpoint = 'https://fapi.binance.com/fapi/v1/order';

        $apiKey = env('BINANCE_API_KEY');
        $apiSecret = env('BINANCE_SECRET_KEY');
        $tpPrice = $data['takeProfit'];

        $precision = $data['qPrecision'];
        $formattedQuantity = number_format($quantity, $precision, '.', '');



        // Prepare the payload for placing the TP order
        $tp_payload = [
            'symbol' => $data['symbol'],
            'side' => 'SELL',
            'quantity' => $formattedQuantity,
            // 'price' => $tpPrice, // TP price
            'type' => 'TAKE_PROFIT_MARKET', // TP order type
            'stopPrice' => $tpPrice, // TP trigger price
            'timestamp' => time() * 1000
        ];

        // Generate the signature for the trade
        $tp_query_string = http_build_query($tp_payload);
        $tp_signature = hash_hmac('sha256', $tp_query_string, $apiSecret);

        // Add the signature to the payload
        $tp_payload['signature'] = $tp_signature;
        try {
            $client = new Client();
            $response = $client->request('POST', $apiEndpoint, [
                'headers' => [
                    'X-MBX-APIKEY' => $apiKey,
                ],
                'form_params' => $tp_payload
            ]);

            $tradeResponse = json_decode($response->getBody(), true);
            $orderId = $tradeResponse['orderId'];

            return $tradeResponse;



            // Handle the response of the opposite trade
            // ...
        } catch (RequestException $e) {
            // Handle the exception for the opposite trade request
            // ...


            Log::error($e);
            Log::error(json_encode($tp_payload));
            return false;
        }
    }

    // send the message to telegram
    private function sendToTelegram(array $trade_data)
    {


        $text = " \n🚀Symbol: " . $trade_data['symbol'] . "\n🚀Entry Price: " . $trade_data['entryPrice'] . "\n🚀Take Profit: " . $trade_data['takeProfit'] . "\n🚀Stop Loss: " . $trade_data['stopLoss'] .
            "\n🚀Current Price: " . $trade_data['currentPrice'] . "\n🚀Trade Type: " . $trade_data['tradeType'] . "\n🚀Margin: " . $trade_data['margin'] . "\n🚀Leverage: " . $trade_data['leverage'] . "X\n🚀qPrecision: " . $trade_data['qPrecision'] .
            "\n🚀pPrecision: " . $trade_data['pPrecision'] . "\n🚀Time: " . date('d-m-Y H:i:s', $trade_data['time']) . " UTC \n🚀Target: " . $trade_data['target'] . "%  \n\n Create your trading website, bots and automate your trading using  @RescronAiBot";

        // Replace with your channel username or ID
        $chat_id = '-' . env('TELEGRAM_CHAT_ID');
        $chat_id = str_replace('--', '-', $chat_id);
        $escapedText = preg_replace('/([\\\\*_\\[\\]~>`\\#+\\-=|{}!.])/u', '\\\\$1', $text);
        $response = Telegram::sendMessage([
            'chat_id' => $chat_id,
            'text' => "*New Future Signal* \n" . $escapedText,
            // 'reply_markup' => $this->reply_markup($text),
            'parse_mode' => 'MarkdownV2'

        ]);
    }
}
