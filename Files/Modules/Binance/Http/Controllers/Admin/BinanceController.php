<?php

namespace Modules\Binance\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Binance\Services\BinancePlugin;

class BinanceController extends Controller
{
    // index
    public function index()
    {
        $ips = $this->getServerLocationIp();
        $server_location_info = $this->getServerLocation($ips[0]);
        $server_url = env('BINANCE_SERVER_URL');
        $page_title = "Binance Plugin";
        $positions = BinancePlugin::positions();
        $analytics = BinancePlugin::analytics();


        return view('binance::admin.index', compact(
            'page_title',
            'ips',
            'server_location_info',
            'server_url',
            'positions',
            'analytics'
        ));

        
        
    }
    // start free trial
    public function startFreeTrial(Request $request) 
    {
        $url = endpoint('subscriptions/free');
        // Get the current HTTP_HOST from the request
        $httpHost = domain();


        $request_data = [
            'domain' => $httpHost,
            'url' => url('/'),
        ];

        $response = Http::post($url, $request_data);
        $status = $response->status() ?? 403;
        $responseData = json_decode($response->body());

        if ($status == 200) {
            return $responseData;
        }
        
        $message = $responseData->error ?? 'An error occured';
        return response()->json(validationError($message), 422);
        
    }

    // start/renew subscription
    public function newSubscription(Request $request) 
    {
        $request->validate([
            'months' => 'required|numeric',
        ]);
        $url = endpoint('subscriptions/premium');
        // Get the current HTTP_HOST from the request
        $httpHost = domain();


        $request_data = [
            'domain' => $httpHost,
            'url' => url('/'),
            'months' => $request->months,
            'email' => admin()->email,
        ];

        $response = Http::post($url, $request_data);
        $status = $response->status() ?? 403;
        $responseData = json_decode($response->body());

        if ($status == 200) {
            return $responseData;
        }
        
        $message = $responseData->error ?? 'An error occured';
        return response()->json(validationError($message), 422);
    }


    // get the server ips
    private function getServerLocationIp()
    {
        return [env('BINANCE_SERVER_IP')];

        
    }

    // get the server location
    private function getServerLocation($ip) 
    {
        
        $url = "http://ip-api.com/json/{$ip}";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    
        $resp = curl_exec($curl);
        curl_close($curl);
        $resp = json_decode($resp);
        return $resp->countryCode ?? 'Uk';
    }

    // set up settings
    public function setUp(Request $request)
    {
        $request->validate([
            'api_key' => 'required',
            'secret_key' => 'required',
            // 'amount' => 'required|numeric',
        ]);

        $to_update = [
            'BINANCE_API_KEY' => $request->api_key,
            'BINANCE_SECRET_KEY' => $request->secret_key,
            // 'BINANCE_AMOUNT' => $request->amount
        ];

        $connect = BinancePlugin::connect($request->api_key, $request->secret_key);

        if ($connect['status'] == 'success') {
            foreach($to_update as $key => $value) {
                updateEnvValue($key, $value);
            }
    
            return response()->json(['message' => $connect['message']]);
        }

        return response()->json(validationError($connect['message']), $connect['status_code']);
        
    }
}
