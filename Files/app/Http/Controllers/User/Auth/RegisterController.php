<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class RegisterController extends Controller
{

    //registration
    public function register()
    {
        $page_title = 'Sign Up';
        //put the referral in session
        if (!session()->get('referred_by')) {
            session()->put('referred_by', request()->ref);
        }

        //flush the register data
        if (session()->get('register_data')) {
            session()->pull('register_data');
        }

        return view(template('user.auth.register'), compact(
            'page_title',
        ));
    }



    //validate email
    public function registerValidate()
    

    {
        $request = request();
        $require_strong_password = site('strong_password');
    
        // 1. Rate limiting by IP
        $executed = RateLimiter::attempt(
            'register-attempts:'.$request->ip(),
            $perMinute = 3,
            function() {}
        );

    
        if (! $executed) {              
            //Some times this may bring error 

            return response()->json(validationError('Too many registration attempts. Please try again later.'), 422);
        }
    
        // 2. Check if email domain exists
        $emailDomain = substr(strrchr($request->email, "@"), 1);
        if (!checkdnsrr($emailDomain, "MX")) {
            return response()->json(validationError('Invalid email domain.'), 422);
        }
    
        // 3. Check against disposable email providers
        $disposableDomains = ['tempmail.com', 'throwawaymail.com'];
        if (in_array($emailDomain, $disposableDomains)) {
            return response()->json(validationError('Disposable email addresses are not allowed.'), 422);
        }
    
        // 4. Add honeypot field validation
        if ($request->filled('contact')) {
            return response()->json(validationError('Invalid request.'), 422);
        }
    
        try {
            // 5. Enhanced validation rules
            $request->validate([
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users',
                    function ($attribute, $value, $fail) {
                        // 6. Check for suspicious patterns in email
                        if (preg_match('/\d{8,}/', $value)) {
                            $fail('This email format is not allowed.');
                        }
                    },
                ],
                'password' => [
                    'required',
                    'confirmed',
                    ($require_strong_password == 1 ? 'min:8' : 'min:5'),
                    function ($attribute, $value, $fail) use ($require_strong_password) {
                        if ($require_strong_password == 1) {
                            if (!preg_match('/\d/', $value)) {
                                $fail('The password must contain a number');
                            } elseif (!preg_match('/[a-z]/', $value)) {
                                $fail('The password must contain a lowercase');
                            } elseif (!preg_match('/[A-Z]/', $value)) {
                                $fail('The password must contain an uppercase');
                            } elseif (!preg_match('/[\W_]/', $value)) {
                                $fail('The password must contain a symbol');
                            }
                        }
                    }
                ],
                // 'g-recaptcha-response' => 'required|recaptcha',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(validationError($e->validator->errors()->first()), 422);
        }
    
        // 8. Additional checks for suspicious patterns
        $suspiciousCount = Cache::get('suspicious-registrations:' . $request->ip(), 0);
        if ($suspiciousCount > 5) {
            return response()->json(validationError('Registration temporarily disabled for security reasons.'), 422);
        }
    
        //log register data
        $register_data = [
            'email' => $request->email,
            'password' => $request->password,
        ];
    
        session()->put('register_data', $register_data);
        $msg = 'Your registration was successful';
    
        if (site('email_v') == 1) {
            //send otp
            try {
                $message = "We have received a registration attempt from this email address. Use the one time passcode below to confirm your registration. If you have not made this request, you can safely ignore this email.";
                sendOtp($request->email, $message);
    
                $msg = 'Enter the OTP code sent to your email to complete your registration. It may take up to 5 minutes to arrive. Check your spam/junk folder if you have not received the code';
            } catch (\Exception $e) {
                return response()->json(validationError('Failed to send OTP. Please try again.'), 422);
            }
        } else {
            try {
                $this->registerUser();
            } catch (\Exception $e) {
                return response()->json(validationError('Registration failed. Please try again.'), 422);
            }
        }
    
        return response()->json([
            'message' => $msg,
            'verify' => site('email_v')
        ]);
    }

    //verify the user
    public function registerVerify()
    {
        $request = request();
        $request->validate(
            [
                'otp' => 'required|numeric',
            ]
        );

        //check if otp is valid
        $register_data = session()->get('register_data');
        if (!validateOtp($request->otp, $register_data['email'])) {
            return response()->json(validationError('Invalid Otp Code'), 422);
        }

        $this->registerUser();

        return response()->json(['message' => 'Otp Code Verified. Redirecting to your dashboard']);
    }


    private function registerUser()
    {
        $register_data = session()->get('register_data');
        $ref = null;
        if (session()->get('referred_by')) {
            $ref = User::where('username', session()->get('referred_by'))->first();
        }

        $amount = site('welcome_bonus');
        //create new user instance
        $user = new User();
        $user->email = $register_data['email'];
        $user->password = Hash::make($register_data['password']);
        $user->email_verified_at = site('email_v') == 1 ? now() : null;
        $user->referred_by = $ref->username ?? null;
        // credit the welcome bonus
        if ($amount > 0) {
            $user->balance = $amount;
        }
        $user->save();

        // credit the welcome bonus
        if (site('welcome_bonus') > 0) {
            recordNewTransaction($amount, $user->id, 'credit', "Welcome Bonus");
        }


        //pull register data from session
        session()->pull('register_data');
        session()->pull('referred_by');

        //login the user in
        session()->put('user', $user->id);
        session()->put('login-otp', 1);

        //send welcome email
        sendWelcomeEmail($user);

        //notify if referred
        if ($ref) {
            sendNewReferralEmail($ref, $user);
        }

    }
}