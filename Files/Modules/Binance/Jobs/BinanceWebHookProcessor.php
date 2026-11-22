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

class BinanceWebHookProcessor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data  = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->placeOrder($this->data);
    }


    private function placeOrder($data)
    {
        $apiEndpoint = 'https://api.binance.com/api/v3/order';

        $apiKey = env('BINANCE_API_KEY');
        $apiSecret = env('BINANCE_SECRET_KEY');
        $current_amount = $this->getCoinBalance('USDT') - 2;
        $amount = floatval(env('BINANCE_AMOUNT'));
        if ($current_amount < $amount)  {
            return false;
        }


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
        $client = new Client();
        try {
            $response = $client->request('POST', $apiEndpoint, [
                'headers' => [
                    'X-MBX-APIKEY' => $apiKey,
                ],
                'form_params' => $payload
            ]);

            $tradeResponse = json_decode($response->getBody(), true);
            $orderId = $tradeResponse['orderId'];



            //place OCO order
            $this->placeOcoOrder($data, $orderId, $apiKey, $apiSecret);
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


    //get copin balance
    private function getCoinBalance($coin)
    {
        $apiKey = env('BINANCE_API_KEY');
        $apiSecret = env("BINANCE_SECRET_KEY");
        $apiEndpoint = 'https://api.binance.com';

        $timestamp = microtime(true) * 1000; // Use microtime to get a more precise timestamp

        // Set the recvWindow value to allow a larger time window (e.g., 10 seconds)
        $recvWindow = 10000;

        // Create the query string parameters
        $params = [
            'timestamp' => number_format($timestamp, 0, '.', ''),
            'recvWindow' => $recvWindow,
        ];

        // Generate the signature
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $apiSecret);

        // Add the signature to the parameters
        $params['signature'] = $signature;

        // Build the request URL
        $requestUrl = $apiEndpoint . '/api/v3/account?' . http_build_query($params);

        // Create the cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-MBX-APIKEY: ' . $apiKey]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the request
        $response = curl_exec($ch);

        // Close the cURL request
        curl_close($ch);

        // Process the response
        $responseData = json_decode($response, true);
        $coin = strtoupper($coin);
        // Check if the request was successful
        if (isset($responseData['balances'])) {
            $usdtBalance = 0; // Initialize with a default value

            // Find the coin balance
            foreach ($responseData['balances'] as $balance) {
                if ($balance['asset'] === $coin) {
                    $usdtBalance = $balance['free'];
                    break;
                }
            }

            // Output the USDT balance
            return floatval($usdtBalance);
        } else {
            Log::error(json_encode($response));
            return $response;

        }
    }

    //Place OCO order
    private function placeOcoOrder($data, $orderId, $apiKey, $apiSecret)
    {
        $apiEndpoint = 'https://api.binance.com/api/v3/order/oco';

        // Determine the opposite trade type
        $oppositeTradeType = 'SELL';

        // Prepare the payload for the opposite trade
        $oppositePayload = [
            'symbol' => $data['symbol'],
            'side' => $oppositeTradeType,
            'quantity' => null, // Will be determined based on the original order
            'price' => $data['takeProfit'],
            'stopPrice' => $data['stopLoss'],
            'stopLimitPrice' => $data['stopLimitPrice'],
            'stopLimitTimeInForce' => 'GTC',
            'timestamp' => time() * 1000
        ];

        // Retrieve the original order details
        $numDecimalPlace = $data['qPrecision'];
        $originalQuantity = $this->getCoinBalance(str_replace('USDT', '', $data['symbol']));

        if ($numDecimalPlace > 0) {
            $multiplier = pow(10, $numDecimalPlace);
            $roundedQuantity = floor($originalQuantity * $multiplier) / $multiplier;
            $formattedQuantity = number_format($roundedQuantity, $numDecimalPlace);
        } else {
            if (str_contains($originalQuantity, '.')) {
                $formattedQuantity = strstr($originalQuantity, '.', true);
            } else {
                $formattedQuantity = $originalQuantity;
            }
        }


        $originalQuantity = str_replace(',', '', $formattedQuantity);
        // $originalQuantity = floatval($originalQuantity);

        // Set the quantity for the opposite trade
        $oppositePayload['quantity'] = $originalQuantity;

        // Generate the signature for the opposite trade
        $oppositeQueryString = http_build_query($oppositePayload);
        $oppositeSignature = hash_hmac('sha256', $oppositeQueryString, $apiSecret);

        // Add the signature to the opposite payload
        $oppositePayload['signature'] = $oppositeSignature;




        // Execute the opposite trade to close the original trade
        $client = new Client();
        try {
            $response = $client->request('POST', $apiEndpoint, [
                'headers' => [
                    'X-MBX-APIKEY' => $apiKey,
                ],
                'form_params' => $oppositePayload
            ]);

            $oppositeTradeResponse = json_decode($response->getBody(), true);

            // Handle the response of the opposite trade
            // ...
        } catch (RequestException $e) {
            // Handle the exception for the opposite trade request
            // ...
            Log::error($e);
            Log::error('PayLoad: ' . json_encode($oppositePayload));
        }
    }
}
