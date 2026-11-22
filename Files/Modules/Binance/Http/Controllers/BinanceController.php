<?php

namespace Modules\Binance\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Binance\Jobs\BinanceWebHookProcessor;
use Modules\Binance\Jobs\BinanceWebHookProcessorFuture;

class BinanceController extends Controller
{
    // receive and process webhook;
    public function WebhookHandler(Request $request) {
        // file_put_contents(base_path('input.json'), json_encode(request()->input()));
        $data = request()->input();
        BinanceWebHookProcessor::dispatch($data)->onQueue('trade');
        return response()->json([
            'Webhook dispatched'
        ]);
    }

    // receive and process webhook for the future endpoint;
    public function WebhookHandlerFuture(Request $request) {
        // file_put_contents(base_path('input.json'), json_encode(request()->input()));
        $data = request()->input();
        BinanceWebHookProcessorFuture::dispatch($data)->onQueue('trade');
        return response()->json([
            'message' => 'Webhook dispatched successfully for future endpoint'
        ]);
    }

}
