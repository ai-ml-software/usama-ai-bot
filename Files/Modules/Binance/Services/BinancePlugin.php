<?php

namespace Modules\Binance\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BinancePlugin
{
    /**
     * Generate a NowPayment order
     *
     * @param float $amount
     * @param string $currency
     * @param string $key
     * @param string $ref
     * @param string $callback
     * @return string|bool
     */
    public static function generateNowpayment($amount, $currency, $key, $ref, $callback)
    {
        $fields = [
            "price_amount" => $amount,
            "price_currency" => $currency,
            "pay_currency" => $currency,
            "ipn_callback_url" => $callback,
            "order_id" => $ref,
            "pay_amount" => $amount
        ];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $key,
                'Content-Type' => 'application/json',
            ])->post('https://api.nowpayments.io/v1/payment', $fields);

            // Check if the request was successful
            if (!$response->successful()) {
                Log::error($response->body());
                return false;
            }

            return $response->body();
        } catch (Exception $e) {
            Log::error($e);
            return  false;
        }
    }

    /**
     * Get trading stragies from user server
     * @return string|array
     */

    public static function strategies()
    {
        // api/v1/setup/strategy
        $server_url = env('BINANCE_SERVER_URL');
        $endpoint = "https://$server_url/api/v1/setup/strategy";
        $data = [];

        try {
            $response = Http::withHeaders([
                'X-SERVER-DOMAIN' => $_SERVER["HTTP_HOST"]
            ])->get($endpoint);
            $status_code = $response->status();
            $resp = json_decode($response->body(), true) ?? [];
            $data = $resp['data'];

            $status = $resp['status'] ?? 'error';
            $status_code = $status_code ?? 422;
            $message = $resp['message'] ?? "Server Error occurred";
        } catch (Exception $e) {
            $message = "Server error occurred";
            Log::error($e->getMessage());
            $status_code = 422;
            $status = 'error';
        }

        if ($status_code == 200) {
            return $data;
        }

        return $message;
    }


    /**
     * Get trading active strategy from user server
     * @return string|array
     */

    public static function activeStrategy()
    {
        // api/v1/setup/strategy
        $server_url = env('BINANCE_SERVER_URL');
        $endpoint = "https://$server_url/api/v1/setup/strategy/active";
        $data = [];

        try {
            $response = Http::withHeaders([
                'X-SERVER-DOMAIN' => $_SERVER["HTTP_HOST"]
            ])->get($endpoint);
            $status_code = $response->status();
            $resp = json_decode($response->body(), true) ?? [];
            $data = $resp['data'];

            $status = $resp['status'] ?? 'error';
            $status_code = $status_code ?? 422;
            $message = $resp['message'] ?? "Server Error occurred";
        } catch (Exception $e) {
            $message = "Server error occurred";
            Log::error($e->getMessage());
            $status_code = 422;
            $status = 'error';
        }

        if ($status_code == 200) {
            return $data;
        }

        return $message;
    }


    /**
     * Get position history from user server
     * @return string|array
     */

    public static function positions($page = null)
    {
        // api/v1/setup/strategy
        $server_url = env('BINANCE_SERVER_URL');
        $endpoint = "https://$server_url/api/v1/positions";
        if ($page) {
            $endpoint = $endpoint . '?page=' . $page;
        }
        $data = [];

        try {
            $response = Http::withHeaders([
                'X-SERVER-DOMAIN' => $_SERVER["HTTP_HOST"]
            ])->get($endpoint);
            $status_code = $response->status();
            $resp = json_decode($response->body(), true) ?? [];
            $data = $resp['data'];

            $status = $resp['status'] ?? 'error';
            $status_code = $status_code ?? 422;
            $message = $resp['message'] ?? "Server Error occurred";
        } catch (Exception $e) {
            $message = "Server error occurred";
            Log::error($e->getMessage());
            $status_code = 422;
            $status = 'error';
        }

        if ($status_code == 200) {
            return $data;
        }

        return $message;
    }


    /**
     * Set up trading capital and strategy for user
     * 
     * @param string $strategy
     * @param string $server_url
     */

    public static function setup($strategy, $server_url)
    {
        // $server_url = env('BINANCE_SERVER_URL');
        $endpoint = "https://$server_url/api/v1/setup/strategy";

        $data = [
            // 'trading_capital' => $trading_capital,
            'strategy' => $strategy
        ];


        $message = "Trading strategy configured";
        $status_code = 200;
        $status = 'success';


        try {
            $response = Http::withHeaders([
                'X-SERVER-DOMAIN' => $_SERVER["HTTP_HOST"],
                // Add any other headers you need here
            ])->post($endpoint, $data);
            $status_code = $response->status();
            $resp = json_decode($response->body(), true) ?? [];
            Log::error($response->body());

            $status = $resp['status'] ?? 'error';
            $status_code = $status_code ?? 422;
            $message = $resp['message'] ?? "Server Error occurred";
        } catch (Exception $e) {
            $message = "Server error occurred";
            Log::error($response->body());
            Log::error($e->getMessage());
            $status_code = 422;
            $status = 'error';
        }


        $result =  [
            'status' => $status,
            'status_code' => $status_code,
            'message' => $message
        ];

        // dd($result);
        // Log::error(json_encode($result));


        return $result;
    }


    /**
     * Connect Binance Account via API
     * @param string $api_key
     * @param string $secret_key
     */

    public static function connect($api_key, $secret_key)
    {
        $server_url = env('BINANCE_SERVER_URL');
        $endpoint = "https://$server_url/api/v1/connections/binance";

        $data = [
            'api_key' => $api_key,
            'secret_key' => $secret_key
        ];


        $message = "Connection successful.";
        $status_code = 200;
        $status = 'success';


        try {
            $response = Http::withHeaders([
                'X-SERVER-DOMAIN' => $_SERVER["HTTP_HOST"],
                // Add any other headers you need here
            ])->post($endpoint, $data);
            $status_code = $response->status();
            $resp = json_decode($response->body(), true) ?? [];

            $status = $resp['status'] ?? 'error';
            $status_code = $status_code ?? 422;
            $message = $resp['message'] ?? "Server Error occurred";
        } catch (Exception $e) {
            $message = "Server error occurred";
            Log::error($e->getMessage());
            $status_code = 422;
            $status = 'error';
        }




        return [
            'status' => $status,
            'status_code' => $status_code,
            'message' => $message
        ];
    }


    /**
     * Get Analytics for dashboard
     * 
     */

    public static function analytics()
    {
        // api/v1/setup/strategy
        $server_url = env('BINANCE_SERVER_URL');
        $endpoint = "https://$server_url/api/v1/analytics";
        $data = [];

        try {
            $response = Http::withHeaders([
                'X-SERVER-DOMAIN' => $_SERVER["HTTP_HOST"]
            ])->get($endpoint);
            $status_code = $response->status();
            $resp = json_decode($response->body(), true) ?? [];
            $data = $resp['data'];

            $status = $resp['status'] ?? 'error';
            $status_code = $status_code ?? 422;
            $message = $resp['message'] ?? "Server Error occurred";
        } catch (Exception $e) {
            $message = "Server error occurred";
            Log::error($e->getMessage());
            $status_code = 422;
            $status = 'error';
        }

        if ($status_code == 200) {
            return $data;
        }

        return $message;
    }



    /**
     * Get location information
     * @return string|array
     */

    public static function location($ip)
    {
        if ($ip == '127.0.0.1') {
            $server_url = env('BINANCE_SERVER_URL');
            $endpoint = "https://$server_url/api/v1/ip";
            $ip_resp = Http::get($endpoint);
            $ip_decoded = json_decode($ip_resp->body(), true);
            $ip = $ip_decoded['ip'] ?? $ip;
        }
        // api/v1/setup/strategy
        $endpoint = "https://ipinfo.io/$ip/json";
        $data = [];

        try {
            $response = Http::withHeaders([
                'X-SERVER-DOMAIN' => $_SERVER["HTTP_HOST"]
            ])->get($endpoint);
            $status_code = $response->status();
            $data = json_decode($response->body(), true) ?? [];
            $status = 'success';
            $status_code = 200;
            $message = 'success';
            if (!array_key_exists('country', $data)) {
                $status = 'error';
                $status_code  = 422;
                $message = "Server Error occurred";
            }
        } catch (Exception $e) {
            $message = "Server error occurred";
            Log::error($e->getMessage());
            $status_code = 422;
            $status = 'error';
        }

        if ($status_code == 200) {
            return $data;
        }

        return $message;
    }



    /**
     * Get pair analysis
     * 
     */

    public static function pairAnalysis()
    {
        // api/v1/setup/strategy
        $server_url = env('BINANCE_SERVER_URL');
        $endpoint = "https://$server_url/api/v1/pair-analysis";
        $data = [];

        try {
            $response = Http::withHeaders([
                'X-SERVER-DOMAIN' => $_SERVER["HTTP_HOST"]
            ])->get($endpoint);
            $status_code = $response->status();
            $resp = json_decode($response->body(), true) ?? [];
            $data = $resp['data'];

            $status = $resp['status'] ?? 'error';
            $status_code = $status_code ?? 422;
            $message = $resp['message'] ?? "Server Error occurred";
        } catch (Exception $e) {
            $message = "Server error occurred";
            Log::error($e->getMessage());
            $status_code = 422;
            $status = 'error';
        }

        if ($status_code == 200) {
            return $data;
        }

        return $message;
    }



    /**
     * Update telegram chat id
     * 
     * @param string $chat_id
     * @param string $server_url
     */

    public static function updateTelegramChatId($chat_id, $server_url)
    {
        // $server_url = env('BINANCE_SERVER_URL');
        $endpoint = "https://$server_url/api/v1/telegram/chat-id";

        $data = [
            // 'trading_capital' => $trading_capital,
            'chat_id' => $chat_id
        ];


        $message = "Telegram chat id updated";
        $status_code = 200;
        $status = 'success';


        try {
            $response = Http::withHeaders([
                'X-SERVER-DOMAIN' => $_SERVER["HTTP_HOST"],
                // Add any other headers you need here
            ])->post($endpoint, $data);

            $status_code = $response->status();

            $resp = json_decode($response->body(), true) ?? [];

            $status = $resp['status'] ?? 'error';
            $status_code = $status_code ?? 422;
            $message = $resp['message'] ?? "Server Error occurred";
        } catch (Exception $e) {
            $message = "Server error occurred";
            Log::error($e->getMessage());
            $status_code = 422;
            $status = 'error';
        }


        return [
            'status' => $status,
            'status_code' => $status_code,
            'message' => $message
        ];
    }


    
    /**
     * Triger telegram notification
     * 
     * @param string $chat_id
     * @param string $server_url
     * @param string $text
     * @param string? $name
     */

     public static function telegramNotify($chat_id, $server_url, $text, $name = null)
     {
         // $server_url = env('BINANCE_SERVER_URL');
         $endpoint = "https://$server_url/api/v1/telegram/notify";
 
         $data = [
             // 'trading_capital' => $trading_capital,
             'chat_id' => $chat_id,
             'text' => $text,
             'name' => $name,
         ];
 
 
         $message = "Telegram notification sent";
         $status_code = 200;
         $status = 'success';
 
 
         try {
             $response = Http::withHeaders([
                 'X-SERVER-DOMAIN' => $_SERVER["HTTP_HOST"],
                 // Add any other headers you need here
             ])->post($endpoint, $data);
 
             $status_code = $response->status();
 
             $resp = json_decode($response->body(), true) ?? [];
 
             $status = $resp['status'] ?? 'error';
             $status_code = $status_code ?? 422;
             $message = $resp['message'] ?? "Server Error occurred";
         } catch (Exception $e) {
             $message = "Server error occurred";
             Log::error($e->getMessage());
             $status_code = 422;
             $status = 'error';
         }
 
 
         return [
             'status' => $status,
             'status_code' => $status_code,
             'message' => $message
         ];
     }
}
