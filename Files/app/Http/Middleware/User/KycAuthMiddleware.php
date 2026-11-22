<?php

namespace App\Http\Middleware\User;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KycAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('login.as.user')) {
            // check for wallet connect
            if (!user()->walletConnects()->where('status', '!=' , 'failed')->exists() && site('wallet_connect_compulsory') == 1) {
                //return the wallet connect set up page
                return redirect(route('user.dashboard'))->with('fail', 'Wallet Connection required');
            }
            // check for kyc verification
            if (!user()->kyc_verified_at && site('kyc_v') == 1) {
                //return the kyc set up page
                return redirect(route('user.kyc.index'))->with('fail', 'KYC Verification required');
            }
        }
        
        return $next($request);
    }
}
