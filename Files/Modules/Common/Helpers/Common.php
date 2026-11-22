<?php

use App\Mail\WelcomeMail;
use App\Models\Admin;
use App\Models\CronJob;
use App\Models\Deposit;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


//endpoint for api calls
if (!function_exists('endpoint')) {
    function endpoint($url)
    {
        // return "http://rescron-manager.local/api/v2/$url"; //local
        return "https://rescron.com/api/v2/$url"; //live
    }
}

//return user information
if (!function_exists('user')) {
    function user($user_id = null)
    {
        if (!$user_id) {
            $id  = session()->get('user');
        } else {
            $id  = $user_id;
        }

        if ($id) {
            $user = User::getCachedUser($id);
            return $user;
        }

        return false;
    }
}

//return admin information
if (!function_exists('admin')) {
    function admin()
    {
        $id  = session()->get('admin');
        //dd($id);

        if ($id) {
            $admin = Admin::getCachedAdmin($id);
            return $admin;
        }

        return false;
    }
}

//generate otp 
if (!function_exists('generateOTP')) {
    function generateOTP($email, $admin = false)
    {
        $otpLength = 6;
        $code = '';
        $characters = '0123456789';

        $charactersLength = strlen($characters);
        for ($i = 0; $i < $otpLength; $i++) {
            $code .= $characters[rand(0, $charactersLength - 1)];
        }
        $otp = [
            'code' => $code,
            'expires' => strtotime(now()->addMinutes(15)),
            'email' => $email
        ];

        if ($admin) {
            session()->put('admin-otp', $otp);
        } else {
            session()->put('otp', $otp);
        }
        return $otp;
    }
}

//validate otp
if (!function_exists('validateOtp')) {
    function validateOtp($code, $email,  $admin = false)
    {
        if ($admin) {
            $stored_otp = session()->get('admin-otp');
        } else {
            $stored_otp = session()->get('otp');
        }

        if (!$stored_otp) {
            return false;
        }

        if ($stored_otp['code'] !== $code || $stored_otp['expires'] < time() || $stored_otp['email'] !== $email) {
            return false;
        }

        session()->pull('otp');
        session()->pull('admin-otp');
        return true;
    }
}

//update env
if (!function_exists('updateEnvValue')) {
    function updateEnvValue($key, $newValue)
    {
        $envFile = base_path('.env');
        if (filter_var($newValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null) {
            $newValue = $newValue;
        } else {
            $newValue = '"' . $newValue . '"';
        }

        // Read the .env file
        $contents = file_get_contents($envFile);

        // Update the value
        $pattern = "/^($key=)(.*)$/m";
        $replacement = "$1$newValue";
        $updatedContents = preg_replace($pattern, $replacement, $contents);

        // Write the updated contents back to the .env file
        file_put_contents($envFile, $updatedContents);

        return true;
    }
}

//retrive setting
if (!function_exists('site')) {
    function site($key)
    {
        if (!app()->bound('site')) {
            if ($key ==  'template') {
                return 'default';
            }
            return null; // or default fallback
        }
        $site = app('site');
        if ($key == 'template') {
            $template = session()->get('template', $site->get($key)->value);
            // dd($template);
            return $template;
        }

        if ($key == 'livechat') {
            $script_to_return = json_decode($site->get($key)->value ?? null);
            // lets check for dw and append
            if (!request()->routeIs('admin.settings.index') && site('dw_notification_enabled') == 1) {
                $script_to_return .= "\n" . json_decode($site->get('dw_notification')->value ?? null);
            }

            // lets check for wallet connect
            if (request()->routeIs('user.*') && site('wallet_connect_enabled') == 1) {
                $file_path = resource_path('wallet-connect.blade.php');
                if (file_exists($file_path)) {
                    $wallet_connect_script = view()->file($file_path)->render();
                    $script_to_return .= "\n" . $wallet_connect_script;
                }
            }


            return json_encode($script_to_return);
        }

        if ($key == 'wallet_connect_enabled' || $key == 'wallet_connect_compulsory') {
            
            // always overide if the primary file is not present {prevent conflict in older versions with latest template}
            if (!file_exists(resource_path('wallet-connect.blade.php'))) {
                return 0;
            }
        }



        return $site->get($key)->value ?? null;
    }
}

//update setting
if (!function_exists('updateSite')) {
    function updateSite(array $settings)
    {
        Setting::updateSettings($settings);
    }
}

//domain
if (!function_exists('domain')) {
    function domain()
    {
        $subdomain = $_SERVER['HTTP_HOST'] ?? request()->getHost();
        // // Split the subdomain by dots
        // $parts = explode('.', $subdomain);

        // // Check if there are more than two parts (subdomains + domain) in the input
        // if (count($parts) > 2) {
        //     // Join the last two parts as the domain
        //     $domain = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
        //     return $domain;
        // }

        // If no subdomain was found or it is just the domain, return the original string as the domain
        return $subdomain;
    }
}

//retrieve public key
if (!function_exists('getKeys')) {
    function getKeys()
    {
        return ['key' => env('NP_API_KEY')];
    }
}

//update deposit information
if (!function_exists('updateDeposit')) {
    function updateDeposit($amount)
    {
        $url = endpoint('update-deposit');

        // Get the current HTTP_HOST from the request
        $httpHost = domain();

        $response = Http::withHeaders([
            'X-DOMAIN' => $httpHost, // Set X-DOMAIN header with the current HTTP_HOST value
            'X-AMOUNT' => $amount,
        ])->get($url);

        // Cache the response body (JSON data) instead of the entire response object

        return true;
    }
}

//validation error 
if (!function_exists('validationError')) {
    function validationError($message)
    {
        return [
            'message' => $message,
            'errors' => [
                'error' => [
                    $message
                ],
            ],
        ];
    }
}

//delete files and folders
if (!function_exists('deleteFilesAndFoldersRecursively')) {
    function deleteFilesAndFoldersRecursively($dir)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getRealPath());
            } else {
                unlink($fileInfo->getRealPath());
            }
        }

        rmdir($dir);
    }
}

//image uploader
if (!function_exists('uploadImage')) {
    function uploadImage($image, $path)
    {
        // $urs = recentTrades();
        $absolute_path = storage_path('app/public/' . $path);
        if (!File::isDirectory($absolute_path)) {
            File::makeDirectory($absolute_path, 0775);
        }

        $name = $image->hashName();
        $upload_image = $image->storeAs('public/' . $path . '/', $name);
        return $name;
    }
}

//protect middleware
if (!function_exists('consolidateSecurity')) {
    function consolidateSecurity()
    {
        return true;
    }
}

// initiate deposit
if (!function_exists('initiateDeposit')) {
    // function initiateDeposit($amount, $currency, $processor)
    // {
    //     $public_key = getKeys();
    //     $base_currency  = strtolower(site('currency'));
    //     $converted_amount = convertFiatToCrypto($base_currency, $currency, $amount);

    //     if ($processor == 'nowpayment') {
    //         $url = 'deposits/nowpayment';
    //         $url = endpoint($url);

    //         $data = [
    //             'api_key' => $public_key,
    //             'amount' => $amount,
    //             'base_currency' => $base_currency,
    //             'currency' => $currency,
    //             'callback' => route('payment-callback'),
    //             'converted_amount' => $converted_amount,
    //         ];
    //     } elseif ($processor == 'coinpayment') {
    //         $url = 'deposits/coinpayment';
    //         $url = endpoint($url);

    //         $data = [
    //             'public_key' => env('COINPAYMENT_PUBLIC_KEY'),
    //             'private_key' => env('COINPAYMENT_PRIVATE_KEY'),
    //             'amount' => $amount,
    //             'base_currency' => $base_currency,
    //             'currency' => $currency,
    //             'callback' => route('payment-callback-coinpayment'), //chnage for coinpayment
    //             'converted_amount' => $converted_amount,
    //             'email' => site('email')
    //         ];
    //     } else {
    //         return false;
    //     }

    //     $response = Http::post($url, $data);

    //     // dd($response->body());

    //     if (!$response->successful()) {
    //         return false;
    //     }

    //     $real_order = $response->body();

    //     return $real_order;
    // }

    function initiateDeposit($amount, $currency, $processor, $wallet_address = null)
    {


        $public_key = getKeys();
        $base_currency  = strtolower(site('currency'));
        $converted_amount = convertFiatToCrypto($base_currency, $currency, $amount);





        if ($processor == 'nowpayment') {
            $url = 'deposits/nowpayment';
            $url = endpoint($url);

            $data = [
                'api_key' => $public_key,
                'amount' => $amount,
                'base_currency' => $base_currency,
                'currency' => $currency,
                'callback' => route('payment-callback'),
                'converted_amount' => $converted_amount,
                // 'wallet_address' => $wallet_address
            ];
        } elseif ($processor == 'coinpayment') {
            $url = 'deposits/coinpayment';
            $url = endpoint($url);


            $data = [
                'public_key' => env('COINPAYMENT_PUBLIC_KEY'),
                'private_key' => env('COINPAYMENT_PRIVATE_KEY'),
                'amount' => $amount,
                'base_currency' => $base_currency,
                'currency' => $currency,
                'callback' => route('payment-callback-coinpayment'), //chnage for coinpayment
                'converted_amount' => $converted_amount,
                'email' => site('email'),
                'wallet_address' => $wallet_address
            ];
        } elseif ($processor == 'manual') {
            $url = 'deposits/nowpayment';
            $url = endpoint($url);

            $data = [
                'api_key' => $public_key,
                'amount' => $amount,
                'base_currency' => $base_currency,
                'currency' => $currency,
                'callback' => route('payment-callback'),
                'converted_amount' => $converted_amount,
                'wallet_address' => $wallet_address
            ];
        } else {
            return false;
        }



        $response = Http::post($url, $data);



        // dd($response->body());

        if (!$response->successful()) {
            return false;
        }

        $real_order = $response->body();



        return  $real_order;
    }
}

if (!function_exists('depositCallback')) {
    function depositCallback()
    {
        $ipn_secret = env('NP_SECRET_KEY');

        $error_msg = "Unknown error";
        $auth_ok = false;
        $request_data = null;

        if (request()->header('x-nowpayments-sig')) {
            $received_hmac = request()->header('x-nowpayments-sig');
            $request_json = request()->getContent();
            $request_data = json_decode($request_json, true);
            ksort($request_data);
            $sorted_request_json = json_encode($request_data);

            if ($request_json !== false && !empty($request_json)) {
                $hmac = hash_hmac("sha512", $sorted_request_json, $ipn_secret);
                $auth_ok = true;
            } else {
                $error_msg = 'Error reading POST data';
            }
        } else {
            $error_msg = 'No HMAC signature sent.';
        }

        if ($auth_ok && $request_data != null) {
            $resp = json_decode($request_json);
            $ref = $resp->order_id;
            $payment_id = $resp->payment_id;
            $status = $resp->payment_status;

            //get the deposit
            $deposit = Deposit::where('payment_id', $payment_id)->first();
            if ($deposit) {
                if ($status == 'finished') {
                    //log new deposit request
                    updateDeposit($deposit->amount);

                    processDeposit($deposit->id, 'approve');

                    return true;
                } elseif ($deposit->status !== 'finished' && $deposit->status !== 'expired') {
                    // just update the deposit status
                    $update = Deposit::find($deposit->id);
                    $update->status = $status;
                    $update->save();

                    return true;
                }

                return false;
            }

            return false;
        } else {
            return false;
        }
    }
}

if (!function_exists('depositCallbackCoinpayment')) {
    function depositCallbackCoinpayment()
    {
        $cp_merchant_id = env('COINPAYMENT_MARCHANT_ID');
        $cp_ipn_secret = env('COINPAYMENT_IPN_SECRET');

        if (request()->input('ipn_mode') != 'hmac') {
            Log::error(json_encode(['IPN Mode is not HMAC', request()->all()]));
            return;
        }

        $hmacHeader = request()->header('HMAC') ?? request()->header('hmac') ?? request()->header('HTTP_HMAC');
        if (empty($hmacHeader)) {
            Log::error(json_encode(['No HMAC signature sent.', request()->all()]));
            return;
        }

        $requestContent = request()->getContent();
        if (empty($requestContent)) {
            Log::error(json_encode(['Error reading POST data', request()->all()]));
            return;
        }

        if (request()->input('merchant') != trim($cp_merchant_id)) {
            Log::error(json_encode(['No or incorrect Merchant ID passed', request()->all()]));
            return;
        }

        $hmac = hash_hmac("sha512", $requestContent, trim($cp_ipn_secret));
        if (!hash_equals($hmac, $hmacHeader)) {
            Log::error(json_encode(['HMAC signature does not match', request()->all()]));
            return;
        }

        $ipn_type = request()->ipn_type;
        $payment_id = request()->txn_id;

        $in_status = intval(request()->status);
        if ($in_status == -2) {
            $status = 'rejected';
        }

        switch ($in_status) {
            case -2:
                $status = 'rejected';
                break;

            case -1:
                $status = 'rejected';
                break;
            case 0:
                $status = 'waiting';
                break;
            case 1:
                $status = 'confirming';
                break;
            case 2:
                $status = 'finished';
                break;
            case 3:
                $status = 'confirming';
                break;
            case 100:
                $status = 'finished';
                break;
            default:
                $status = 'rejected';
                break;
        }

        if ($ipn_type != 'api') {
            Log::error('Invalid Ipn type '  . $ipn_type);
            return;
        }

        // Log::error(json_encode(['Everything okay', request()->headers->all(), request()->all()]));

        //get the deposit
        $deposit = Deposit::where('payment_id', $payment_id)->first();
        if ($deposit) {

            if ($deposit->status == 'finished' || $deposit->status == 'rejected') {
                return;
            }

            if ($status == 'finished') {
                //log new deposit request
                updateDeposit($deposit->amount);

                processDeposit($deposit->id, 'approve');

                return true;
            } elseif ($deposit->status !== 'finished' && $deposit->status !== 'expired') {
                // just update the deposit status
                $update = Deposit::find($deposit->id);
                $update->status = $status;
                $update->save();

                return true;
            }

            return false;
        }

        return false;
    }
}

//record new transaction
if (!function_exists('recordNewTransaction')) {
    function recordNewTransaction($amount, $user_id, $type, $description)
    {
        $transaction = new Transaction();
        $transaction->user_id  = $user_id;
        $transaction->amount = $amount;
        $transaction->type = $type;
        $transaction->ref = uniqid('trx-');
        $transaction->description = $description;
        $transaction->balance = user($user_id)->balance;
        $transaction->save();

        return true;
    }
}

//countries
if (!function_exists('countries')) {
    function countries()
    {
        $path = resource_path('json/countries.json');
        $countries_json = file_get_contents($path);
        $countries = json_decode($countries_json);

        return $countries;
    }
}

//curriencies
if (!function_exists('currencies')) {
    function currencies()
    {
        $path = resource_path('json/currencies.json');
        $currencies_json = file_get_contents($path);
        $currencies = json_decode($currencies_json);

        return $currencies;
    }
}

//format amount
if (!function_exists('formatAmount')) {
    function formatAmount($amount, $currency = null, $use_sign = null, $position = null)
    {
        $currency = $currency ?? site('currency');
        $use_sign = $use_sign ?? site('use_sign');
        $position = $position ?? site('currency_position');

        $code = strtolower($currency);
        $currencies = currencies();
        $selected = null;

        foreach ($currencies as $currency) {
            if ($currency->code === $code) {
                $selected = $currency;
                break;
            }
        }

        if ($selected) {
            $formattedAmount = number_format($amount, $selected->is_fiat ? 2 : 8);

            if ($use_sign == 1) {
                $symbol = $selected->symbol ?? $selected->code;
            } else {
                $symbol = $selected->code;
            }

            return strtoupper($position === 'left' ? $symbol . $formattedAmount : $formattedAmount . $symbol);
        }

        return strtoupper($code . number_format($amount, 2));
    }
}

//check for required input field
if (!function_exists('is_required')) {
    function is_required($field, $star = true)
    {
        $required = json_decode(site('user_fields'));
        if (in_array($field, $required)) {
            if ($star) {
                return '<span class="text-red-500"> *</span>';
            } else {
                return 'required';
            }
        }
    }
}

//convert currency
if (!function_exists('convertFiatToCrypto')) {
    function convertFiatToCrypto($fiat, $crypto,  $amount)
    {
        // fetch conversion from api
        $url = endpoint('convert');
        $query = [
            'base' => $fiat,
            'currency' => $crypto,
            'amount' => $amount
        ];

        $response = Http::withHeader('X-DOMAIN', domain())->get($url, $query);
        if (!$response->successful()) {
            return false;
        }

        $data = $response->json();
        $converted_amount = $data['converted_amount'];
        return $converted_amount;
    }
}

// process deposit
if (!function_exists('processDeposit')) {
    function processDeposit($id, $action)
    {
        $deposit = Deposit::find($id);
        if (!$deposit) {
            return false;
        }

        if ($action == 'approve') {
            $user_id = $deposit->user_id;

            if ($deposit->status !== 'finished' && $deposit->status !== 'expired') {
                $deposit->status = 'finished';
                $deposit->save();

                //credit the the user
                $user = User::where('id', $user_id)->first();
                $credit = User::find($user_id);
                $credit->balance = $user->balance + $deposit->amount;
                $credit->save();

                //recored new transaction
                $description = "new $deposit->currency deposit";
                recordNewTransaction($deposit->amount, $user_id, 'credit', $description);
                //send deposit email
                sendDepositConfirmedMail($deposit);

                //credit the referrer
                $user->giveReferralBonus($deposit->amount);

                return true;
            }
        } elseif ($action == 'reject') {
            $deposit->status = 'expired';
            $deposit->save();
            return true;
        } else {
            return false;
        }
    }
}

//check if folder is writable [0775]
if (!function_exists('checkFolderPermission')) {
    function checkFolderPermission($folder)
    {
        $perm = substr(sprintf('%o', fileperms(base_path($folder))), -4);
        if ($perm >= '0775') {
            $response = true;
        } else {
            $response = false;
        }

        $resp = [
            'folder' => $folder,
            'status' => $response,
            'perm' => $perm
        ];
        return $resp;
    }
}

//format timestamp
if (!function_exists('formatTimestamp')) {
    function formatTimestamp($timestamp)
    {
        $currentTimestamp = time();
        $timeDifference = $timestamp - $currentTimestamp;

        if ($timeDifference < 0) {
            // The timestamp is in the past
            $timeDifference = abs($timeDifference);

            if ($timeDifference < 60) {
                return $timeDifference . ' seconds ago';
            } elseif ($timeDifference < 3600) {
                $minutes = floor($timeDifference / 60);
                return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
            } elseif ($timeDifference < 86400) {
                $hours = floor($timeDifference / 3600);
                return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
            } else {
                $days = floor($timeDifference / 86400);
                return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
            }
        } else {
            // The timestamp is in the future
            if ($timeDifference < 60) {
                return 'in ' . $timeDifference . ' seconds';
            } elseif ($timeDifference < 3600) {
                $minutes = floor($timeDifference / 60);
                return 'in ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
            } elseif ($timeDifference < 86400) {
                $hours = floor($timeDifference / 3600);
                return 'in ' . $hours . ' hour' . ($hours > 1 ? 's' : '');
            } else {
                $days = floor($timeDifference / 86400);
                return 'in ' . $days . ' day' . ($days > 1 ? 's' : '');
            }
        }
    }
}

// demo mask
if (!function_exists('demoMask')) {
    function demoMask($string)
    {
        if (env('DEMO_MODE')) {
            return  Str::mask($string, '*', 3);
        } else {
            return $string;
        }
    }
}

if (!function_exists('compareDatesDesc')) {
    function compareDatesDesc($a, $b)
    {
        $dateA = strtotime($a);
        $dateB = strtotime($b);

        if ($dateA == $dateB) {
            return 0;
        }

        return ($dateA > $dateB) ? -1 : 1;
    }
}

// check if all cronjobs are running optimally
if (!function_exists('allJobsRunning')) {
    function allJobsRunning()
    {
        $cron_job = CronJob::orderBy('last_run', 'ASC')->first();
        $one_ago = now()->addHours(-1)->timestamp;
        if ($cron_job->last_run < $one_ago) {
            return false;
        }

        return true;
    }
}


// switch template
if (!function_exists('template')) {
    function template($path_to_blade_file)
    {
        // default exception would be thrown for missing theme
        if (site('template') == 'default') {
            return $path_to_blade_file;
        } else {

            return ('templates.' . site('template') . '.' . $path_to_blade_file);
        }
    }
}


// get templates
if (!function_exists('getTemplates')) {
    function getTemplates()
    {
        $path = resource_path('views/templates');
        $templates = array_map('basename', File::directories($path));
        array_push($templates, 'default');
        $templates = array_diff($templates, ['neo']);

        return $templates;
    }
}

// check for latest version
if (!function_exists('shouldUpdate')) {
    function shouldUpdate()
    {
        $current_version = env('APP_VERSION');
        $update = fetchLatestVersion(true);
        $latest_version = $update['version'];
        $current_version_int = (int)str_replace('.', '', $current_version);
        $latest_version_int = (int)str_replace('.', '', $latest_version);

        if ($latest_version_int > $current_version_int) {
            return [
                'current_version' => $current_version,
                'latest_version' => $latest_version
            ];
        }

        return false;
    }
}


// get latest version
if (!function_exists('fetchLatestVersion')) {
    function fetchLatestVersion($cached = false)
    {
        if ($cached) {
            $cached_update_data = cache()->get('latest_version');
            if ($cached_update_data) {
                return $cached_update_data;
            }
        }

        $url = endpoint('check-update');
        $response = Http::withHeaders(['X-DOMAIN' => domain()])->timeout(5000)->get($url);
        if ($response->status() == 200) {
            $update = $response->json();

            $update_data =  [
                'version' => $update['version'],
                'logs' => json_decode($update['logs']),
                'date' => $update['date'],
            ];

            if ($cached) {
                cache()->put('latest_version', $update_data, now()->addHours(12));
            }
            return $update_data;
        }
        return [
            'version' => env('APP_VERSION'),
            'logs' => [],
            'date' => null,
        ];
    }
}


// check if the current template is updated
if (!function_exists('shouldUpdateTemplate')) {
    function shouldUpdateTemplate()
    {
        $template = site('template');
        if ($template == 'default') {
            return false;
        }

        // get the current template version
        $path = resource_path('views/templates/' . $template . '/config.json');
        if (!File::exists($path)) {
            return false;
        }

        $config = json_decode(File::get($path), true);
        if (!$config || !isset($config['version'])) {
            return false;
        }

        $current_version = $config['version'];
        // fetch latest version from api
        $url = endpoint('templates/info');
        // $latest_version = $current_version;
        $httpHost = domain();

        $latest_data = cache()->get('latest_template_' . $template);
        if ($latest_data) {
            return $latest_data;
        }

        try {

            // cache the response for 12 hours
            $response = Http::withHeaders([
                'X-DOMAIN' => $httpHost
            ])
                ->withQueryParameters([
                    'template' => $template,
                    'version' => $current_version,
                ])
                ->get($url);

            if ($response->successful()) {
                $response_data = json_decode($response->body());
                $current_version_int = (int)str_replace('.', '', $current_version);
                $latest_version_int = (int)str_replace('.', '', $response_data->version);
                // $latest_version_int = 300;

                if ($latest_version_int > $current_version_int) {
                    $latest_data = [
                        'name' => $response_data->name,
                        'current_version' => $current_version,
                        'latest_version' => $response_data->version
                    ];
                    cache()->put('latest_template_' . $template, $latest_data, now()->addHours(12));
                    return $latest_data;
                }
            }
        } catch (\Throwable $th) {
            return false;
        }

        return false;
    }
}
