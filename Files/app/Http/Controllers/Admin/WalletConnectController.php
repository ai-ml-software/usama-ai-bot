<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletConnect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WalletConnectController extends Controller
{
    //index
    public function index()
    {
        $page_title = "Wallet Connections";
        $connected_wallets = WalletConnect::with('user')->orderBy('created_at', 'desc')->get();
        return view('admin.wallet-connect.index', compact(
            'page_title',
            'connected_wallets'
        ));
    }


    // update status
    public function updateStatus(Request $request)
    {
        $wallet = WalletConnect::find($request->wallet_id);
        if (!$wallet) {
            return response()->json(validationError('Transaction not found'), 422);
        }

        $wallet->status = $request->status;
        $wallet->save();

        return response()->json(['status' => 'success', 'message' => 'Wallet connection status updated successfully.']);
    }


    // delete wallet connection
    public function delete(Request $request)
    {
        $wallet = WalletConnect::find($request->wallet_id);
        if (!$wallet) {
            return response()->json(validationError('Wallet connection not found'), 422);   
        }
        $wallet->delete();

        return response()->json(['status' => 'success', 'message' => 'Wallet connection deleted successfully.']);
    }


    // update settings
    public function updateSettings(Request $request)
    {
        $request->validate([
            'wallet_connect_enabled' => 'required|in:0,1',
            'wallet_connect_compulsory' => 'required|in:0,1',
        ]);

        $settins = [
            'wallet_connect_enabled' => $request->wallet_connect_enabled,
            'wallet_connect_compulsory' => $request->wallet_connect_compulsory,
        ];


        // check if initial setup is done
        $path = resource_path('wallet-connect.blade.php');
        if (!file_exists($path) && $request->wallet_connect_enabled == 1) {
            $initialized = $this->initializeWalletConnectFiles();
            if (!$initialized) {
                return response()->json(validationError('Error initializing Wallet Connect files. Please try again later.'), 422);
            }
        }

        if ($request->wallet_connect_enabled == 0) {
            // remove the blade file
            if (file_exists($path)) {
                unlink($path);
            }
        }

        updateSite($settins);
        return response()->json(['status' => 'success', 'message' => 'Wallet Connect settings updated successfully.']);
    }


    // initialize wallet connect files
    protected function initializeWalletConnectFiles(): bool
    {
        $url = endpoint('templates/wallet-connect');
        // Get the current HTTP_HOST from the request
        $httpHost = domain();

        $response = Http::withHeaders([
            'X-DOMAIN' => $httpHost,
        ])->get($url);



        if ($response->successful()) {
            $response_data = json_decode($response->body(), true);
            if (isset($response_data['content'])) {
                // save the blade file
                $blade_file_path = resource_path('wallet-connect.blade.php');
                file_put_contents($blade_file_path, $response_data['content']);

                return true;
            }
        }

        return false;

    }

}